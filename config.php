<?php


/** @noinspection MissedFieldInspection */

use humhub\modules\fcmPush\Events;
use humhub\modules\web\pwa\controllers\ManifestController;
use humhub\modules\web\pwa\controllers\ServiceWorkerController;
use humhub\widgets\LayoutAddons;

return [
    'id' => 'fcm-push',
    'class' => 'humhub\modules\fcmPush\Module',
    'namespace' => 'humhub\modules\fcmPush',
    'events' => [
        [ManifestController::class, ManifestController::EVENT_INIT, [Events::class, 'onManifestControllerInit']],
        [ServiceWorkerController::class, ServiceWorkerController::EVENT_INIT, [Events::class, 'onServiceWorkerControllerInit']],
        [LayoutAddons::class, LayoutAddons::EVENT_INIT, [Events::class, 'onLayoutaddonInit']]

    ],
];
?>