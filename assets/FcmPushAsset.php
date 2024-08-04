<?php

namespace humhub\modules\fcmPush\assets;

use Yii;
use yii\helpers\Url;
use yii\web\AssetBundle;
use humhub\widgets\Button;
use humhub\modules\fcmPush\Module;
use humhub\modules\fcmPush\services\DriverService;

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

            return parent::register($view);
        }
    }
}
