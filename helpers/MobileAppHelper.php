<?php

namespace humhub\modules\fcmPush\helpers;

use humhub\helpers\DeviceDetectorHelper;
use humhub\modules\file\Module;
use Yii;
use yii\helpers\Json;
use yii\helpers\Url;

class MobileAppHelper extends \humhub\helpers\MobileAppHelper
{
    public const SESSION_VAR_REGISTER_NOTIFICATION = 'mobileAppRegisterNotification';
    public const SESSION_VAR_UNREGISTER_NOTIFICATION = 'mobileAppUnregisterNotification';

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
            'contentCreateUrl' => Url::to(['/file/share-intend/index'], true),
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
}
