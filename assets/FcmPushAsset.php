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


        Yii::$app->view->registerJsConfig('firebase', [
            'tokenUpdateUrl' => Url::to(['/fcm-push/token/update']),
            'senderId' => $pushDriver->getSenderId(),
        ]);

        return parent::register($view);
    }
}
