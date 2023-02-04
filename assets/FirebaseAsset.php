<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\fcmPush\assets;

use humhub\modules\ui\view\components\View;
use yii\web\AssetBundle;

class FirebaseAsset extends AssetBundle
{
    public $defer = false;

    public $publishOptions = [
        'forceCopy' => false,

    ];

    public $sourcePath = '@fcm-push/vendor/npm-asset/firebase';

    public $js = [
        'firebase-app.js',
        'firebase-messaging.js',
        //'https://www.gstatic.com/firebasejs/6.3.3/firebase-app.js',
        //'https://www.gstatic.com/firebasejs/6.3.3/firebase-messaging.js'
    ];

}
