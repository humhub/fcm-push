<?php

namespace humhub\modules\fcmPush\helpers;

use humhub\helpers\DeviceDetectorHelper;
use Yii;

class WebAppHelper
{
    public const SESSION_VAR_UNREGISTER_NOTIFICATION = 'mobileAppUnregisterNotification';

    public static function unregisterNotificationScript()
    {
        if (DeviceDetectorHelper::isAppRequest()) {
            return;
        }

        Yii::$app->view->registerJs('humhub.modules.firebase.unregisterNotification();');
    }
}
