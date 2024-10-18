<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\fcmPush\assets;

use humhub\components\assets\AssetBundle;

class FirebaseAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $defer = false;

    /**
     * @inheritdoc
     */
    public $sourcePath = '@fcm-push/vendor/npm-asset/firebase';

    /**
     * @inheritdoc
     */
    public $js = [
        'firebase-app-compat.js',
        'firebase-messaging-compat.js',
    ];
}
