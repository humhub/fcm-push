<?php

namespace humhub\modules\fcmPush\assets;

use humhub\modules\fcmPush\Module;
use humhub\modules\fcmPush\services\DriverService;
use Yii;
use yii\helpers\Url;
use yii\web\AssetBundle;

class FcmPushAsset extends AssetBundle
{
    public $defer = false;

    public $publishOptions = [
        'forceCopy' => true,
    ];

    public $sourcePath = '@fcm-push/resources/js';

    public $js = [
        'humhub.firebase.js',
    ];

    public static function register($view)
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        $pushDriver = (new DriverService($module->getConfigureForm()))->getWebDriver();
        if ($pushDriver !== null) {
            Yii::$app->view->registerJsConfig('firebase', [
                'tokenUpdateUrl' => Url::to(['/fcm-push/token/update']),
                'senderId' => $pushDriver->getSenderId(),
            ]);

            Yii::$app->view->registerJsConfig('firebase', [
                'text' => [
                    'status.granted' => Yii::t('FcmPushModule.base', 'Granted: Push Notifications are active on this browser.<br>You can disable it in browser settings for this site.'),
                    'status.denied' => Yii::t('FcmPushModule.base', 'Denied: You have blocked Push Notifications.<br>You can enable it in browser settings for this site.'),
                    'status.default' => Yii::t('FcmPushModule.base', 'Default: Push Notifications are not yet enabled.<br><button id="enablePushBtn" class="btn btn-primary"><i class="fa fa-unlock"></i> Click here to enable</button>'),
                    'status.not-supported' => Yii::t('FcmPushModule.base', 'Not Supported: This browser does not support notifications.'),
                ],
            ]);

            return parent::register($view);
        }
    }
}
