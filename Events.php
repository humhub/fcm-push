<?php

namespace humhub\modules\fcmPush;

use humhub\modules\fcmPush\assets\FcmPushAsset;
use humhub\modules\fcmPush\assets\FirebaseAsset;
use humhub\modules\fcmPush\components\NotificationTargetProvider;
use humhub\modules\fcmPush\helpers\MobileAppHelper;
use humhub\modules\fcmPush\services\DriverService;
use humhub\modules\fcmPush\widgets\PushNotificationInfoWidget;
use humhub\modules\notification\targets\MobileTargetProvider;
use humhub\modules\web\pwa\controllers\ManifestController;
use humhub\modules\web\pwa\controllers\ServiceWorkerController;
use humhub\widgets\BaseStack;
use Yii;

class Events
{
    private const SESSION_VAR_LOGOUT = 'mobileAppHandleLogout';
    private const SESSION_VAR_LOGIN = 'mobileAppHandleLogin';

    public static function onBeforeRequest($event)
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
            importScripts('{$bundle->baseUrl}/firebase-app.js');
            importScripts('{$bundle->baseUrl}/firebase-messaging.js');
            //importScripts('https://www.gstatic.com/firebasejs/6.3.3/firebase-app.js');
            //importScripts('https://www.gstatic.com/firebasejs/6.3.3/firebase-messaging.js');
        
           firebase.initializeApp({messagingSenderId: "{$pushDriver->getSenderId()}"});
            
            const messaging = firebase.messaging();
            messaging.setBackgroundMessageHandler(function(payload) {
              const notificationTitle = payload.data.title;
              const notificationOptions = {
                body: payload.data.body,
                icon: payload.data.icon
              };
              return self.registration.showNotification(notificationTitle, notificationOptions);
            });
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
        if (Yii::$app->session->has(self::SESSION_VAR_LOGOUT)) {
            MobileAppHelper::registerLogoutScript();
            Yii::$app->session->remove(self::SESSION_VAR_LOGOUT);
        }

        if (Yii::$app->session->has(self::SESSION_VAR_LOGIN)) {
            MobileAppHelper::registerLoginScript();
            MobileAppHelper::registerNotificationScript();
            Yii::$app->session->remove(self::SESSION_VAR_LOGIN);
        }

        if (Yii::$app->user->isGuest) {
            return;
        }

        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        if (!$module->getDriverService()->hasConfiguredWebDriver()) {
            return;
        }

        FcmPushAsset::register(Yii::$app->view);
        FirebaseAsset::register(Yii::$app->view);
    }

    public static function onAfterLogout()
    {
        Yii::$app->session->set(self::SESSION_VAR_LOGOUT, 1);
    }

    public static function onAfterLogin()
    {
        Yii::$app->session->set(self::SESSION_VAR_LOGIN, 1);
    }
}
