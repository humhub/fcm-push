<?php

namespace humhub\modules\fcmPush\helpers;

use Yii;
use yii\helpers\Json;
use yii\helpers\Url;

class MobileAppHelper
{
    public const SESSION_VAR_SHOW_OPENER = 'mobileAppShowOpener';
    /**
     * @deprecated Remove when minimal HumHub mobile app support is v1.0.124 and later
     */
    public const SESSION_VAR_HIDE_OPENER = 'mobileAppHideOpener';
    public const SESSION_VAR_REGISTER_NOTIFICATION = 'mobileAppRegisterNotification';
    public const SESSION_VAR_UNREGISTER_NOTIFICATION = 'mobileAppUnregisterNotification';

    public static function registerHideOpenerScript()
    {
        if (!static::isAppRequest()) {
            return;
        }

        $json = ['type' => 'hideOpener'];
        $message = Json::encode($json);

        self::sendFlutterMessage($message);
    }

    public static function registerShowOpenerScript()
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

    public static function isIosApp(): bool
    {
        return
            static::isAppRequest()
            && Yii::$app->request->headers->get('x-humhub-app-is-ios');
    }

    /**
     * True if the mobile app supports multi instance to display the Opener landing page without logout for switching instance
     *
     * @since HumHub mobile app v1.0.124
     */
    public static function isMultiInstanceApp(): bool
    {
        return
            static::isAppRequest()
            && Yii::$app->request->headers->get('x-humhub-app-is-multi-instance');
    }

    /**
     * True if the mobile app Opener landing page is visible and should be hidden.
     *
     * @since HumHub mobile app v1.0.124
     */
    public static function openerState(): bool
    {
        return
            static::isAppRequest()
            && Yii::$app->request->headers->get('x-humhub-app-opener-state');
    }
}
