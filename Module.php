<?php

namespace humhub\modules\fcmPush;

use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\fcmPush\services\DriverService;
use humhub\modules\fcmPush\services\GoService;
use Yii;
use yii\helpers\Url;

class Module extends \humhub\components\Module
{
    /**
     * @inheritdoc
     */
    public $resourcesPath = 'resources';
    public string $humhubProxySenderId = '21392898126';
    private ?ConfigureForm $configForm = null;
    private ?DriverService $driverService = null;
    private ?GoService $goService = null;

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

    public function getDriverService(): DriverService
    {
        if ($this->driverService === null) {
            $this->driverService = new DriverService($this->getConfigureForm());
        }
        return $this->driverService;
    }

    public function getGoService(): GoService
    {
        if ($this->goService === null) {
            $this->goService = new GoService('https://go.humhub.com');
        }
        return $this->goService;
    }

    public static function registerAutoloader()
    {
        require Yii::getAlias('@fcm-push/vendor/autoload.php');
    }

}
