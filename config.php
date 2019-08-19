<?php


/** @noinspection MissedFieldInspection */

use humhub\modules\fcmPush\Events;
use humhub\modules\web\pwa\controllers\ManifestController;

return [
    'id' => 'fcm-push',
    'class' => 'humhub\modules\fcmPush\Module',
    'namespace' => 'humhub\modules\fcmPush',
    'events' => [
        [ManifestController::class, ManifestController::EVENT_INIT, [Events::class, 'onManifestControllerInit']]
    ],
];
?>