<?php

namespace humhub\modules\fcmPush\events;

use humhub\components\Event;
use humhub\modules\user\models\User;

/**
 * NotificationCountEvent is triggered when the push notification badge count is calculated
 * for a user. Other modules can add their own counts (e.g. unseen conversation messages)
 * to the `count` property.
 *
 * @since 2.2.9
 */
class NotificationCountEvent extends Event
{
    /**
     * @var User the user the notification count is calculated for
     */
    public User $user;

    /**
     * @var int the total notification count. Event handlers should add their own count to this value.
     */
    public int $count = 0;
}
