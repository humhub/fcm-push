<?php


/** @noinspection MissedFieldInspection */

use humhub\modules\fcmPush\Events;
use humhub\modules\notification\events\UnreadCountChangedEvent;
use humhub\modules\notification\widgets\NotificationSettingsForm;
use humhub\modules\user\components\User;
use humhub\services\ServiceWorkerService;
use humhub\widgets\LayoutAddons;
use yii\base\Application;

//use humhub\modules\notification\widgets\NotificationInfoWidget;

return [
    'id' => 'fcm-push',
    'class' => 'humhub\modules\fcmPush\Module',
    'namespace' => 'humhub\modules\fcmPush',
    'events' => [
        [ServiceWorkerService::class, ServiceWorkerService::EVENT_BUILD_SCRIPT, [Events::class, 'onBuildServiceWorkerScript']],
        [LayoutAddons::class, LayoutAddons::EVENT_INIT, [Events::class, 'onLayoutAddonInit']],
        [Application::class, Application::EVENT_BEFORE_REQUEST, [Events::class, 'onBeforeRequest']],
        [User::class, User::EVENT_AFTER_LOGIN, [Events::class, 'onAfterLogin']],
        [User::class, User::EVENT_AFTER_LOGOUT, [Events::class, 'onAfterLogout']],
        [NotificationSettingsForm::class, NotificationSettingsForm::EVENT_AFTER_RUN, [Events::class, 'onNotificationSettingsFormAfterRun']],
        [UnreadCountChangedEvent::class, UnreadCountChangedEvent::EVENT_UNREAD_COUNT_CHANGED, [Events::class, 'onUnreadCountChanged']],
    ],
    'consoleControllerMap' => [
        'firebase' => 'humhub\modules\fcmPush\commands\SendController',
    ],
];
