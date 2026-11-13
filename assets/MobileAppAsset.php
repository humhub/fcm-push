<?php

namespace humhub\modules\fcmPush\assets;

use humhub\components\assets\AssetBundle;
use yii\helpers\Url;

/**
 * Keeps the app icon badge of the mobile app in sync while a page is open, see
 * `humhub.fcmPush.mobileApp.js`. Only registered for mobile app requests.
 *
 * @since 2.3.3
 */
class MobileAppAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@fcm-push/resources/js';

    /**
     * @inheritdoc
     */
    public $js = [
        'humhub.fcmPush.mobileApp.js',
    ];

    /**
     * @inheritdoc
     */
    public static function register($view)
    {
        $view->registerJsConfig('fcmPush.mobileApp', [
            'notificationCountUrl' => Url::to(['/fcm-push/mobile-app/notification-count']),
        ]);

        return parent::register($view);
    }
}
