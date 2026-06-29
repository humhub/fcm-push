<?php

namespace humhub\modules\fcmPush;

use humhub\modules\fcmPush\assets\FcmPushAsset;
use humhub\modules\fcmPush\assets\FirebaseAsset;
use humhub\modules\fcmPush\components\NotificationTargetProvider;
use humhub\modules\fcmPush\helpers\MobileAppHelper;
use humhub\modules\fcmPush\helpers\WebAppHelper;
use humhub\modules\fcmPush\services\DriverService;
use humhub\modules\fcmPush\widgets\RegisterDeviceTokenButton;
use humhub\modules\notification\targets\MobileTargetProvider;
use humhub\modules\web\pwa\controllers\ServiceWorkerController;
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

        $bundle = FirebaseAsset::register(Yii::$app->view);

        $pushDriver = (new DriverService($module->getConfigureForm()))->getWebDriver();

        // Service Worker Addons
        $controller->additionalJs .= <<<JS
            // Give the service worker access to Firebase Messaging.
            importScripts('{$bundle->baseUrl}/firebase-app-compat.js');
            importScripts('{$bundle->baseUrl}/firebase-messaging-compat.js');

            firebase.initializeApp({
                messagingSenderId: "{$pushDriver->getSenderId()}",
                projectId: "{$module->getConfigureForm()->getJsonParam('project_id')}",
                appId: "{$module->getConfigureForm()->firebaseAppId}",
                apiKey: "{$module->getConfigureForm()->firebaseApiKey}",
            });

            // Initialize Firebase Cloud Messaging and get a reference to the service
            firebase.messaging();
JS;
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
        }
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
    }

    public static function onAfterLogout()
    {
        Yii::$app->session->set(WebAppHelper::SESSION_VAR_UNREGISTER_NOTIFICATION, 1);
        Yii::$app->session->set(MobileAppHelper::SESSION_VAR_UNREGISTER_NOTIFICATION, 1);
    }

    public static function onNotificationSettingsFormAfterRun(WidgetEvent $event)
    {
        $event->result = RegisterDeviceTokenButton::widget() . $event->result;
    }
}
