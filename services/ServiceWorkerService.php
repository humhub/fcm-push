<?php

namespace humhub\modules\fcmPush\services;

use humhub\modules\fcmPush\assets\FirebaseAsset;
use humhub\modules\fcmPush\Module;
use Yii;
use yii\helpers\Json;

/**
 * Builds the JavaScript appended to the PWA service worker
 * (see Events::onServiceWorkerControllerInit()).
 */
class ServiceWorkerService
{
    public function __construct(private Module $module)
    {
    }

    public function getJs(): string
    {
        $bundle = FirebaseAsset::register(Yii::$app->view);
        $baseUrl = Yii::getAlias($bundle->baseUrl);

        $configureForm = $this->module->getConfigureForm();
        $pushDriver = (new DriverService($configureForm))->getWebDriver();

        // Settings are admin input and must be encoded as JS literals, otherwise a quote breaks the whole service worker
        $messagingSenderId = Json::encode((string)$pushDriver->getSenderId());
        $projectId = Json::encode((string)$configureForm->getJsonParam('project_id'));
        $appId = Json::encode((string)$configureForm->firebaseAppId);
        $apiKey = Json::encode((string)$configureForm->firebaseApiKey);

        // Notification click handling (must be registered before the Firebase importScripts)
        $js = file_get_contents(dirname(__DIR__) . '/resources/js/humhub.firebase.worker.js');

        // Give the service worker access to Firebase Messaging.
        $js .= <<<JS
            importScripts('{$baseUrl}/firebase-app-compat.js');
            importScripts('{$baseUrl}/firebase-messaging-compat.js');

            firebase.initializeApp({
                messagingSenderId: {$messagingSenderId},
                projectId: {$projectId},
                appId: {$appId},
                apiKey: {$apiKey},
            });

            // Initialize Firebase Cloud Messaging and get a reference to the service
            firebase.messaging();
JS;

        return $js;
    }
}
