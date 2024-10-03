<?php

namespace humhub\modules\fcmPush\components;

use humhub\modules\fcmPush\Module;
use humhub\modules\fcmPush\services\MessagingService;
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
        Yii::$app->i18n->setUserLocale($user);

        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        (new MessagingService($module->getConfigureForm()))
            ->processNotification($notification, $user);

        Yii::$app->i18n->autosetLocale();

        return true;
    }


    /**
     * @inheritDoc
     */
    public function isActive(User $user = null)
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        if (!$module->getDriverService()->hasConfiguredDriver()) {
            return false;
        }

        // Check if user has at least one token
        #if ($user !== null && FcmUser::find()->where(['user_id' => $user->id])->count() === 0) {
        #    return false;
        #}

        return true;
    }
}
