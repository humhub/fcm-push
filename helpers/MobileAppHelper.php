<?php

namespace humhub\modules\fcmPush\helpers;

use humhub\helpers\DeviceDetectorHelper;
use humhub\modules\fcmPush\Module;
use humhub\modules\fcmPush\services\MessagingService;
use Yii;
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

    /**
     * Whether the unread count is sent to the mobile app of this request, which shows it as app
     * icon badge: for logged-in users in the app, if the app gets push messages. Without them,
     * nothing would update the badge while the app is closed.
     *
     * @since 2.3.5
     */
    public static function isBadgeSyncEnabled(): bool
    {
        if (!DeviceDetectorHelper::isAppRequest() || Yii::$app->user->isGuest) {
            return false;
        }

        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        return $module->getDriverService()->getMobileAppDriver() !== null;
    }

    /**
     * Returns the unread count for the app icon badge of the mobile app of this request, `null`
     * if it is not sent to this request (see isBadgeSyncEnabled()) or could not be calculated.
     *
     * @since 2.3.5
     */
    public static function getBadgeCount(): ?int
    {
        if (!static::isBadgeSyncEnabled()) {
            return null;
        }

        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        try {
            return (new MessagingService($module->getConfigureForm()))
                ->getNotificationCount(Yii::$app->user->getIdentity());
        } catch (\Throwable $e) {
            // Other modules add their counts, a failure there must not break the page
            Yii::error('Could not calculate the mobile app badge count: ' . $e->getMessage(), 'fcm-push');
            return null;
        }
    }

    /**
     * Sends the unread count to the mobile app, which shows it as app icon badge.
     *
     * @since 2.3.5
     */
    public static function updateNotificationCountScript(int $count): void
    {
        if (!DeviceDetectorHelper::isAppRequest()) {
            return;
        }

        $json = ['type' => 'updateNotificationCount', 'count' => max(0, $count)];
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
