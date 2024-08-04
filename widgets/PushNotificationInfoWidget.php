<?php

namespace humhub\modules\fcmPush\widgets;

use Yii;
use yii\helpers\Url;
use humhub\components\Widget;
use humhub\modules\fcmPush\Module;
use humhub\modules\fcmPush\services\DriverService;

class PushNotificationInfoWidget extends Widget
{
    /**
     * @inheritdoc
     */
    public function run()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');
        $pushDriver = (new DriverService($module->getConfigureForm()))->getWebDriver();

        if ($pushDriver === null) {
            return '';
        }

        return $this->render('push-notification-info', [
            'tokenUpdateUrl' => Url::to(['/fcm-push/token/update']),
            'senderId' => $pushDriver->getSenderId(),
        ]);
    }
}
