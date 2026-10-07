<?php

namespace humhub\modules\fcmPush;

use humhub\modules\fcmPush\assets\FcmPushAsset;
use humhub\modules\fcmPush\assets\FirebaseAsset;
use humhub\modules\fcmPush\components\NotificationTargetProvider;
use humhub\modules\fcmPush\helpers\MobileAppHelper;
use humhub\modules\fcmPush\helpers\WebAppHelper;
use humhub\modules\fcmPush\jobs\SendSilentUnreadNotificationCountJob;
use humhub\modules\fcmPush\services\ServiceWorkerService;
use humhub\modules\fcmPush\widgets\EnableNotificationsBanner;
use humhub\modules\fcmPush\widgets\RegisterDeviceTokenButton;
use humhub\modules\notification\events\UnreadCountChangedEvent;
use humhub\modules\notification\targets\MobileTargetProvider;
use humhub\modules\notification\widgets\NotificationSettingsForm;
use humhub\modules\web\pwa\controllers\ServiceWorkerController;
use humhub\helpers\DeviceDetectorHelper;
use humhub\widgets\LayoutAddons;
use Yii;
use yii\base\WidgetEvent;

class Events
{
    public static function onBeforeRequest()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        // Replace the core MobileTargetProvider binding with our own implementation so that
        // HumHub's notification module dispatches mobile notifications through FCM.
        // This is done here (not in config.php) because we only want to override when at least
        // one driver is actually configured — otherwise the original provider is left intact.
        if ($module->getDriverService()->hasConfiguredDriver()) {
            Yii::$container->set(MobileTargetProvider::class, NotificationTargetProvider::class);
        }
    }

    public static function onServiceWorkerControllerInit($event): void
    {
        /** @var ServiceWorkerController $controller */
        $controller = $event->sender;

        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        if (!$module->getDriverService()->hasConfiguredWebDriver()) {
            return;
        }

        // Service Worker Addons
        $controller->additionalJs .= (new ServiceWorkerService($module))->getJs();
    }

    public static function onLayoutAddonInit($event)
    {
        // After login: the session flag set by onAfterLogin is consumed here so the
        // registration script runs exactly once on the first post-login page render.
        if (Yii::$app->session->has(MobileAppHelper::SESSION_VAR_REGISTER_NOTIFICATION)) {
            MobileAppHelper::registerNotificationScript();
            Yii::$app->session->remove(MobileAppHelper::SESSION_VAR_REGISTER_NOTIFICATION);
        }

        // After logout: unregister tokens so this device stops receiving push notifications.
        // The session flags are set by onAfterLogout and consumed once here.
        if (Yii::$app->session->has(WebAppHelper::SESSION_VAR_UNREGISTER_NOTIFICATION)) {
            static::registerAssets();
            WebAppHelper::unregisterNotificationScript();
            Yii::$app->session->remove(WebAppHelper::SESSION_VAR_UNREGISTER_NOTIFICATION);
        }
        if (Yii::$app->session->has(MobileAppHelper::SESSION_VAR_UNREGISTER_NOTIFICATION)) {
            MobileAppHelper::unregisterNotificationScript();
            Yii::$app->session->remove(MobileAppHelper::SESSION_VAR_UNREGISTER_NOTIFICATION);
        }

        if (!Yii::$app->user->isGuest) {
            static::registerAssets();
            static::addEnableNotificationsBanner($event->sender);
        }
    }

    /**
     * After login: show the enable-notifications banner once per login session, on the
     * first full page the user reaches after all user gates (legal confirmation, 2FA check,
     * forced password change, ...) are closed. The session flag set by onAfterLogin is
     * consumed here, so the banner never comes back during this login, whether the user
     * enabled notifications, closed it or just navigated away.
     * Whether the banner is actually visible is decided in the browser: only when the
     * Notification API exists and permission is not granted (see EnableNotificationsBanner).
     */
    private static function addEnableNotificationsBanner(LayoutAddons $layoutAddons): void
    {
        if (!Yii::$app->session->has(WebAppHelper::SESSION_VAR_SHOW_ENABLE_NOTIFICATIONS_BANNER)) {
            return;
        }

        // Native apps register tokens through the Flutter bridge (MobileAppHelper), not the
        // browser Notification API. PJAX responses do not render layout addons anyway.
        if (DeviceDetectorHelper::isAppRequest() || Yii::$app->request->isPjax) {
            return;
        }

        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');
        if (!$module->getDriverService()->hasConfiguredWebDriver()) {
            Yii::$app->session->remove(WebAppHelper::SESSION_VAR_SHOW_ENABLE_NOTIFICATIONS_BANNER);
            return;
        }

        // Keep the flag while a gate is open: the gate page itself must not show the banner,
        // the first regular page after the gate flow does.
        if (WebAppHelper::hasOpenGate()) {
            return;
        }

        Yii::$app->session->remove(WebAppHelper::SESSION_VAR_SHOW_ENABLE_NOTIFICATIONS_BANNER);
        $layoutAddons->addWidget(EnableNotificationsBanner::class);
    }

    private static function registerAssets()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        if (!$module->getDriverService()->hasConfiguredWebDriver()) {
            return;
        }

        FcmPushAsset::register(Yii::$app->view);
        FirebaseAsset::register(Yii::$app->view);
    }

    public static function onAfterLogin()
    {
        Yii::$app->session->set(MobileAppHelper::SESSION_VAR_REGISTER_NOTIFICATION, 1);
        Yii::$app->session->set(WebAppHelper::SESSION_VAR_SHOW_ENABLE_NOTIFICATIONS_BANNER, 1);
    }

    public static function onAfterLogout()
    {
        Yii::$app->session->set(WebAppHelper::SESSION_VAR_UNREGISTER_NOTIFICATION, 1);
        Yii::$app->session->set(MobileAppHelper::SESSION_VAR_UNREGISTER_NOTIFICATION, 1);
    }

    public static function onNotificationSettingsFormAfterRun(WidgetEvent $event)
    {
        /** @var NotificationSettingsForm $form */
        $form = $event->sender;

        if ($form->model->user) { // Only show the button for User settings (not admin settings)
            $event->result = RegisterDeviceTokenButton::widget() . $event->result;
        }
    }

    /**
     * Pushes a delayed, exclusive-per-user job that sends the updated unread notification
     * count as a silent push to the user's devices, whenever the count changed.
     *
     * The delay collapses multiple count changes within a short time frame into a single
     * push carrying the final count
     */
    public static function onUnreadCountChanged(UnreadCountChangedEvent $event): void
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        if (!$module->getDriverService()->hasConfiguredDriver()) {
            return;
        }

        Yii::$app->queue->delay($module->silentUnreadNotificationCountPushDelay)->push(new SendSilentUnreadNotificationCountJob([
            'userId' => $event->user->id,
        ]));
    }
}
