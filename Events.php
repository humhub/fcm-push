<?php

namespace humhub\modules\fcmPush;

use humhub\helpers\DeviceDetectorHelper;
use humhub\modules\fcmPush\assets\FcmPushAsset;
use humhub\modules\fcmPush\assets\FirebaseAsset;
use humhub\modules\fcmPush\components\NotificationTargetProvider;
use humhub\modules\fcmPush\helpers\MobileAppHelper;
use humhub\modules\fcmPush\helpers\WebAppHelper;
use humhub\modules\fcmPush\services\DriverService;
use humhub\modules\fcmPush\widgets\PushNotificationInfoWidget;
use humhub\modules\notification\targets\MobileTargetProvider;
use humhub\modules\ui\menu\MenuLink;
use humhub\modules\user\widgets\AccountTopMenu;
use humhub\modules\user\widgets\AuthChoice;
use humhub\modules\web\pwa\controllers\ManifestController;
use humhub\modules\web\pwa\controllers\ServiceWorkerController;
use humhub\widgets\BaseStack;
use Yii;
use yii\base\Event;

class Events
{
    public static function onBeforeRequest()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        if ($module->getDriverService()->hasConfiguredDriver()) {
            Yii::$container->set(MobileTargetProvider::class, NotificationTargetProvider::class);
        }
    }

    public static function onManifestControllerInit($event)
    {
        /** @var ManifestController $controller */
        $controller = $event->sender;
        $controller->manifest['gcm_sender_id'] = (string)103953800507;
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

    public static function onNotificationInfoWidget($event)
    {
        /** @var BaseStack $baseStack */
        $baseStack = $event->sender;

        $baseStack->addWidget(PushNotificationInfoWidget::class);
    }

    public static function onLayoutAddonInit($event)
    {
        // If the mobile app Opener page is open (after login and switching instance)
        if (Yii::$app->session->has(MobileAppHelper::SESSION_VAR_HIDE_OPENER)) {
            MobileAppHelper::registerHideOpenerScript();
            Yii::$app->session->remove(MobileAppHelper::SESSION_VAR_HIDE_OPENER);
        } elseif (DeviceDetectorHelper::appOpenerState()) {
            MobileAppHelper::registerHideOpenerScript();
        }

        // After login
        if (Yii::$app->session->has(MobileAppHelper::SESSION_VAR_REGISTER_NOTIFICATION)) {
            MobileAppHelper::registerNotificationScript();
            Yii::$app->session->remove(MobileAppHelper::SESSION_VAR_REGISTER_NOTIFICATION);
        }

        // After logout
        if (Yii::$app->session->has(WebAppHelper::SESSION_VAR_UNREGISTER_NOTIFICATION)) {
            static::registerAssets();
            WebAppHelper::unregisterNotificationScript();
            Yii::$app->session->remove(WebAppHelper::SESSION_VAR_UNREGISTER_NOTIFICATION);
        }
        if (Yii::$app->session->has(MobileAppHelper::SESSION_VAR_UNREGISTER_NOTIFICATION)) {
            MobileAppHelper::unregisterNotificationScript();
            Yii::$app->session->remove(MobileAppHelper::SESSION_VAR_UNREGISTER_NOTIFICATION);
        }
        if (Yii::$app->session->has(MobileAppHelper::SESSION_VAR_SHOW_OPENER)) {
            MobileAppHelper::registerShowOpenerScript();
            Yii::$app->session->remove(MobileAppHelper::SESSION_VAR_SHOW_OPENER);
        }

        // Get info for the Share intend feature (uploading files from the mobile app)
        MobileAppHelper::getFileUploadSettings();

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
        Yii::$app->session->set(MobileAppHelper::SESSION_VAR_HIDE_OPENER, 1);
        Yii::$app->session->set(MobileAppHelper::SESSION_VAR_REGISTER_NOTIFICATION, 1);
    }

    public static function onAfterLogout()
    {
        Yii::$app->session->set(WebAppHelper::SESSION_VAR_UNREGISTER_NOTIFICATION, 1);
        Yii::$app->session->set(MobileAppHelper::SESSION_VAR_UNREGISTER_NOTIFICATION, 1);
        Yii::$app->session->set(MobileAppHelper::SESSION_VAR_SHOW_OPENER, 1);
    }

    public static function onAuthChoiceBeforeRun(Event $event)
    {
        /** @var AuthChoice $sender */
        $sender = $event->sender;

        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        if (DeviceDetectorHelper::isIosApp() && $module->getConfigureForm()->disableAuthChoicesIos) {
            $sender->setClients([]);
        }
    }

    public static function onAccountTopMenuInit(Event $event)
    {
        if (!DeviceDetectorHelper::isMultiInstanceApp()) {
            return;
        }

        /** @var AccountTopMenu $menu */
        $menu = $event->sender;

        $menu->addEntry(new MenuLink([
            'label' => Yii::t('FcmPushModule.base', 'Switch network'),
            'url' => ['/fcm-push/mobile-app/instance-opener'],
            'icon' => 'arrows-h',
            'sortOrder' => 699, // Just before "Logout"
            'isVisible' => true,
        ]));
    }
}
