<?php

namespace humhub\modules\fcmPush;

use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\fcmPush\services\DriverService;
use Yii;
use yii\helpers\Url;

class Module extends \humhub\components\Module
{
    public string $humhubProxySenderId = '21392898126';

    /**
     * @var int delay in seconds before a silent unread notification count push is sent.
     * Multiple count changes for the same user within this window are collapsed into a
     * single push carrying the final count.
     * @since 2.2.9
     */
    public int $silentUnreadNotificationCountPushDelay = 60 * 10;
    private ?ConfigureForm $configForm = null;
    private ?DriverService $driverService = null;

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

    public static function registerAutoloader()
    {
        require Yii::getAlias('@fcm-push/vendor/autoload.php');
    }

}
