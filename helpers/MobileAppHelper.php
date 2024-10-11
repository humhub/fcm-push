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

        $json = ['type' => 'registerFcmDevice', 'url' => Url::to(['/fcm-push/token/update-mobile-app'], true)];
        $message = Json::encode($json);
        self::sendFlutterMessage($message);
    }

    public static function unregisterNotificationScript()
    {
        if (!static::isAppRequest()) {
            return;
        }

        $json = ['type' => 'unregisterFcmDevice', 'url' => Url::to(['/fcm-push/token/delete-mobile-app'], true)];
        $message = Json::encode($json);
        self::sendFlutterMessage($message);
    }

    public static function isAppRequest()
    {
        return (
            (Yii::$app->request->headers->get('x-requested-with', null, true) === 'com.humhub.app') ||
            (Yii::$app->request->headers->has('x-humhub-app'))
        );
    }

    /**
     * Determines whether the app is a branded app with custom firebase configuration.
     * @return bool
     */
    public static function isAppWithCustomFcm(): bool
    {
        return (
            Yii::$app->request->headers->has('x-humhub-app-bundle-id') &&
            !str_contains(
                Yii::$app->request->headers->get('x-humhub-app-bundle-id', '', true),
                'com.humhub.app',
            )
        );

    }

    private static function sendFlutterMessage($msg)
    {
        Yii::$app->view->registerJs('if (window.flutterChannel) { window.flutterChannel.postMessage(\'' . $msg . '\'); }');
    }

    public static function isIosApp()
    {
        $headers = Yii::$app->request->headers;

        if (static::isAppRequest() &&
            $headers->has('user-agent') &&
            str_contains($headers->get('user-agent', '', true), 'iPhone')) {

            return true;
        }
    }

}
