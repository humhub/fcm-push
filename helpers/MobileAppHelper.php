<?php

namespace humhub\modules\fcmPush\helpers;

use humhub\helpers\DeviceDetectorHelper;
use humhub\modules\file\Module;
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

    public static function registerHideOpenerScript(): void
    {
        if (!DeviceDetectorHelper::isAppRequest()) {
            return;
        }

        $json = ['type' => 'hideOpener'];
        $message = Json::encode($json);

        self::sendFlutterMessage($message);
    }

    public static function registerShowOpenerScript(): void
    {
        if (!DeviceDetectorHelper::isAppRequest()) {
            return;
        }

        $json = ['type' => 'showOpener'];
        $message = Json::encode($json);

        self::sendFlutterMessage($message);
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

    /**
     * @since 2.1.5
     */
    public static function getFileUploadSettings(): void
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('file');

        $json = [
            'type' => 'fileUploadSettings',
            'fileUploadUrl' => Url::to(['/file/file/upload'], true),
            'contentCreateUrl' => Url::to(['/content/share-intend/target'], true),
            'maxFileSize' => $module->settings->get('maxFileSize'),
            'allowedExtensions' => $module->settings->get('allowedExtensions'),
            'imageMaxResolution' => $module->imageMaxResolution,
            'imageJpegQuality' => $module->imageJpegQuality,
            'imagePngCompressionLevel' => $module->imagePngCompressionLevel,
            'imageWebpQuality' => $module->imageWebpQuality,
            'imageMaxProcessingMP' => $module->imageMaxProcessingMP,
            'denyDoubleFileExtensions' => $module->denyDoubleFileExtensions,
        ];

        $message = Json::encode($json);
        self::sendFlutterMessage($message);
    }

    private static function sendFlutterMessage($msg): void
    {
        Yii::$app->view->registerJs('if (window.flutterChannel) { window.flutterChannel.postMessage(\'' . $msg . '\'); }');
    }
}
