<?php

namespace humhub\modules\fcmPush\jobs;

use humhub\modules\fcmPush\Module;
use humhub\modules\fcmPush\services\MessagingService;
use humhub\modules\queue\ActiveJob;
use humhub\modules\queue\interfaces\ExclusiveJobInterface;
use humhub\modules\user\models\User;
use Yii;

/**
 * Sends a silent (data-only) push notification carrying the current unread notification
 * count to a user's registered devices, so the mobile / PWA app can update its badge count.
 *
 * This job is exclusive per user and is pushed with a delay (see the FCM push event handler),
 * so multiple unread-count changes within a short time frame are collapsed into a single push
 * carrying the final, up-to-date count.
 *
 * @since 2.2.9
 */
class SendSilentUnreadNotificationCountJob extends ActiveJob implements ExclusiveJobInterface
{
    /**
     * @var int the id of the user whose unread notification count has changed
     */
    public $userId;

    /**
     * @inheritdoc
     */
    public function getExclusiveJobId()
    {
        return 'fcm-push.silent-unread-count.' . $this->userId;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $user = User::findOne(['id' => $this->userId]);
        if ($user === null) {
            return;
        }

        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        (new MessagingService($module->getConfigureForm()))
            ->sendSilentUnreadNotificationCount($user);
    }
}
