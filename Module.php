<?php

namespace humhub\modules\fcmPush;

use humhub\modules\fcmPush\models\ConfigureForm;
use Yii;
use yii\helpers\Url;
use Kreait\Firebase\Factory;

class Module extends \humhub\components\Module
{

    /**
     * @inheritdoc
     */
    public $resourcesPath = 'resources';
    public string $humhubProxySenderId = '21392898126';

    private ?ConfigureForm $configForm = null;

    /**
     * @inheritdoc
     */
    public function getConfigUrl()
    {
        return Url::to(['/fcm-push/admin']);
    }

    public function getConfigureForm(): ConfigureForm
    {
        if ($this->configForm === null) {
            $this->configForm = new ConfigureForm();
            $this->configForm->loadSettings();
        }
        return $this->configForm;
    }

    public static function registerAutoloader()
    {
        require Yii::getAlias('@fcm-push/vendor/autoload.php');
    }

}
