<?php

namespace humhub\modules\fcmPush\helpers;

use Yii;
use yii\helpers\Json;
use yii\helpers\Url;

class MobileAppHelper
{


    public static function registerLoginScript()
    {

        if (!static::isAppRequest()) {
            return;
        }

        $json = ['type' => 'hideOpener'];
        $message = Json::encode($json);

        self::sendFlutterMessage($message);
    }

    public static function registerLogoutScript()
    {
        if (!static::isAppRequest()) {
            return;
        }

        $json = ['type' => 'showOpener'];
        $message = Json::encode($json);

        self::sendFlutterMessage($message);
    }

    public static function registerNotificationScript()
    {
        if (!static::isAppRequest()) {
            return;
        }

        $json = ['type' => 'registerFcmDevice', 'url' => Url::to(['/fcm-push/token/update'], true)];
        $message = Json::encode($json);
        self::sendFlutterMessage($message);
    }

    public static function isAppRequest()
    {
        return (
            (Yii::$app->request->headers->has('x-humhub-app'))
        );
    }

    private static function sendFlutterMessage($msg)
    {
        Yii::$app->view->registerJs('if (window.flutterChannel) { window.flutterChannel.postMessage(\'' . $msg . '\'); }');
    }

}