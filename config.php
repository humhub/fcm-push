<?php


/** @noinspection MissedFieldInspection */

use humhub\modules\fcmPush\Events;
use humhub\components\Controller;
use humhub\widgets\LayoutAddons;
use yii\base\Application;



return [
    'id' => 'fcm-push',
    'class' => 'humhub\modules\fcmPush\Module',
    'namespace' => 'humhub\modules\fcmPush',
    'events' => [
        ['humhub\modules\web\pwa\controllers\ManifestController', Controller::EVENT_INIT, [Events::class, 'onManifestControllerInit']],
        ['humhub\modules\web\pwa\controllers\ServiceWorkerController', Controller::EVENT_INIT, [Events::class, 'onServiceWorkerControllerInit']],
        [LayoutAddons::class, LayoutAddons::EVENT_INIT, [Events::class, 'onLayoutaddonInit']],
        [Application::class, Application::EVENT_BEFORE_REQUEST, [Events::class, 'onBeforeRequest']],
    ],
];
?>