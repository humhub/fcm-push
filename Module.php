<?php

namespace humhub\modules\fcmPush;

use yii\helpers\Url;

class Module extends \humhub\components\Module
{

    /**
     * @inheritdoc
     */
    public $resourcesPath = 'resources';


    public function getConfigUrl()
    {
        return Url::to(['/mobile/settings']);
    }

}
