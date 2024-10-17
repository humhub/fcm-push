<?php

namespace humhub\modules\fcmPush;

use humhub\components\mail\Message;
use humhub\modules\fcmPush\assets\FcmPushAsset;
use humhub\modules\fcmPush\assets\FirebaseAsset;
use humhub\modules\fcmPush\components\MailerMessage;
use humhub\modules\fcmPush\components\NotificationTargetProvider;
use humhub\modules\fcmPush\helpers\MobileAppHelper;
use humhub\modules\fcmPush\services\DriverService;
use humhub\modules\fcmPush\widgets\PushNotificationInfoWidget;
use humhub\modules\notification\targets\MobileTargetProvider;
use humhub\modules\user\widgets\AuthChoice;
use humhub\modules\web\pwa\controllers\ManifestController;
use humhub\modules\web\pwa\controllers\ServiceWorkerController;
use humhub\widgets\BaseStack;
use Yii;
use yii\base\Event;

class Events
{
    private const SESSION_VAR_LOGOUT = 'mobileAppHandleLogout';
    private const SESSION_VAR_LOGIN = 'mobileAppHandleLogin';

    public static function onBeforeRequest()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        if ($module->getDriverService()->hasConfiguredDriver()) {
            Yii::$container->set(MobileTargetProvider::class, NotificationTargetProvider::class);
        }

        if ($module->getGoService()->isConfigured()) {
            Yii::$container->set(Message::class, MailerMessage::class);
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
        if (Yii::$app->session->has(self::SESSION_VAR_LOGOUT)) {
            MobileAppHelper::unregisterNotificationScript(); // Before Logout
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

    public static function onAuthChoiceBeforeRun(Event $event)
    {
        /** @var AuthChoice $sender */
        $sender = $event->sender;

        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        if (MobileAppHelper::isIosApp() && $module->getConfigureForm()->disableAuthChoicesIos) {
            $sender->setClients([]);
        }
    }
}
