<?php

namespace humhub\modules\fcmPush\services;

use humhub\modules\fcmPush\driver\DriverInterface;
use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\notification\components\BaseNotification;
use humhub\modules\user\models\User;
use humhub\modules\web\pwa\widgets\SiteIcon;
use Yii;
use yii\helpers\Url;

class MessagingService
{
    /**
     * @var DriverInterface[]
     */
    private array $drivers;

    public function __construct(ConfigureForm $config)
    {
        $this->drivers = (new DriverService($config))->getConfiguredDrivers();
    }

    public function processNotification(BaseNotification $baseNotification, User $user): void
    {
        $this->processMessage(
            $user,
            Yii::$app->name,
            $baseNotification->text(),
            Url::to(['/notification/entry', 'id' => $baseNotification->record->id], true),
            SiteIcon::getUrl(180),
        );
    }

    public function processMessage(User $user, string $title, string $body, ?string $url, ?string $imageUrl)
    {
        foreach ($this->drivers as $driver) {
            $tokens = (new TokenService())->getTokensForUser($user, $driver);
            $driver->processCloudMessage($tokens, $title, $body, $url, $imageUrl);
        }

    }

}