<?php

namespace humhub\modules\fcmPush\components;

use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\fcmPush\models\FcmUser;
use Yii;
use yii\base\Component;
use humhub\modules\notification\components\BaseNotification;
use humhub\modules\notification\targets\MobileTargetProvider;
use humhub\modules\user\models\User;

class NotificationTargetProvider extends Component implements MobileTargetProvider
{
    /**
     * @inheritDoc
     */
    public function init()
    {
        // Init module
        Yii::$app->getModule('fcm-push');
        parent::init();
    }


    /**
     * @inheritDoc
     */
    public function handle(BaseNotification $notification, User $user)
    {
        $api = new FcmApi();
        return $api->sendNotification($notification, $user);
    }


    /**
     * @inheritDoc
     */
    public function isActive(User $user = null)
    {
        if (!ConfigureForm::getInstance()->isActive()) {
            return false;
        }

        // Check if user has at least one token
        if ($user !== null && FcmUser::find()->where(['user_id' => $user->id])->count() === 0) {
            return false;
        }

        return true;
    }
}