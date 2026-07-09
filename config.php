<?php


/** @noinspection MissedFieldInspection */

use humhub\components\Controller;
use humhub\modules\fcmPush\Events;
use humhub\modules\notification\widgets\NotificationSettingsForm;
use humhub\modules\user\components\User;
use humhub\widgets\LayoutAddons;
use yii\base\Application;

//use humhub\modules\notification\widgets\NotificationInfoWidget;

return [
    'id' => 'fcm-push',
    'class' => 'humhub\modules\fcmPush\Module',
    'namespace' => 'humhub\modules\fcmPush',
    'events' => [
        ['humhub\modules\web\pwa\controllers\ServiceWorkerController', Controller::EVENT_INIT, [Events::class, 'onServiceWorkerControllerInit']],
        [LayoutAddons::class, LayoutAddons::EVENT_INIT, [Events::class, 'onLayoutAddonInit']],
        [Application::class, Application::EVENT_BEFORE_REQUEST, [Events::class, 'onBeforeRequest']],
        [User::class, User::EVENT_AFTER_LOGIN, [Events::class, 'onAfterLogin']],
        [User::class, User::EVENT_AFTER_LOGOUT, [Events::class, 'onAfterLogout']],
        [NotificationSettingsForm::class, NotificationSettingsForm::EVENT_AFTER_RUN, [Events::class, 'onNotificationSettingsFormAfterRun']],
    ],
    'consoleControllerMap' => [
        'firebase' => 'humhub\modules\fcmPush\commands\SendController',
    ],
];
