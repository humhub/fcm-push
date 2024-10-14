<?php

namespace humhub\modules\fcmPush\assets;

use humhub\components\assets\AssetBundle;
use humhub\modules\fcmPush\Module;
use humhub\modules\fcmPush\services\DriverService;
use Yii;
use yii\helpers\Url;

class FcmPushAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $defer = false;

    /**
     * @inheritdoc
     */
    public $sourcePath = '@fcm-push/resources/js';

    /**
     * @inheritdoc
     */
    public $js = [
        'humhub.firebase.js',
    ];

    /**
     * @inheritdoc
     */
    public static function register($view)
    {
        /* @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        $pushDriver = (new DriverService($module->getConfigureForm()))->getWebDriver();
        if ($pushDriver !== null) {
            Yii::$app->view->registerJsConfig('firebase', [
                'tokenUpdateUrl' => Url::to(['/fcm-push/token/update']),
                'senderId' => $pushDriver->getSenderId(),
                'projectId' => $module->getConfigureForm()->getJsonParam('project_id'),
                'apiKey' => $module->getConfigureForm()->firebaseApiKey,
                'appId' => $module->getConfigureForm()->firebaseAppId,
                'vapidKey' => $module->getConfigureForm()->firebaseVapidKey,
            ]);
        }

        return parent::register($view);
    }
}
