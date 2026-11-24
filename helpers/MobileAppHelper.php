<?php

namespace humhub\modules\fcmPush\helpers;

use humhub\helpers\DeviceDetectorHelper;
use yii\helpers\Json;
use yii\helpers\Url;

class MobileAppHelper extends \humhub\helpers\MobileAppHelper
{
    public const SESSION_VAR_REGISTER_NOTIFICATION = 'mobileAppRegisterNotification';
    public const SESSION_VAR_UNREGISTER_NOTIFICATION = 'mobileAppUnregisterNotification';

    /**
     * Returns the base URL of this network, sent with push messages so the mobile app can tell
     * which of its networks a message belongs to, e.g. to attribute the unread count of a silent
     * push message. It is the `start_url` of the PWA manifest, which the app stores per network.
     *
     * In web requests the value comes from the request. In console requests, e.g. the queue job
     * sending a push message, it comes from the "Base URL" setting, so both match as long as that
     * setting is the URL the network is opened with.
     *
     * @since 2.3.3
     */
    public static function getBaseUrl(): string
    {
        return Url::base(true);
    }

    public static function registerNotificationScript(): void
    {
        if (!DeviceDetectorHelper::isAppRequest()) {
            return;
        }

        $json = ['type' => 'registerFcmDevice', 'url' => Url::to(['/fcm-push/token/update-mobile-app'], true)];
        $message = Json::encode($json);
        self::sendFlutterMessage($message);
    }

    public static function unregisterNotificationScript(): void
    {
        if (!DeviceDetectorHelper::isAppRequest()) {
            return;
        }

        $json = ['type' => 'unregisterFcmDevice', 'url' => Url::to(['/fcm-push/token/delete-mobile-app'], true)];
        $message = Json::encode($json);
        self::sendFlutterMessage($message);
    }
}
