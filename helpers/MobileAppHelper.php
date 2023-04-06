<?php

namespace humhub\modules\fcmPush\helpers;

use Yii;
use yii\helpers\Json;

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

        $json = ['type' => 'registerFcmDevice', 'url' => \yii\helpers\Url::to(['/mobile-app/register'], true)];
        $message = Json::encode($json);
        self::sendFlutterMessage($message);
    }

    private static function isAppRequest()
    {
        return (Yii::$app->request->headers->get('x-requested-with', null, true) === 'com.humhub.app');
    }

    private static function sendFlutterMessage($msg)
    {
        Yii::$app->view->registerJs('if (window.flutterChannel) { window.flutterChannel.postMessage(\'' . $msg . '\'); }');
    }

}