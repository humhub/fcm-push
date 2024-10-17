<?php

namespace humhub\modules\fcmPush\services;

use humhub\modules\fcmPush\driver\DriverInterface;
use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\notification\components\BaseNotification;
use humhub\modules\notification\models\Notification as NotificationHumHub;
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
            NotificationHumHub::findUnseen($user)->count(),
        );
    }

    public function processMessage(User $user, string $title, string $body, ?string $url, ?string $imageUrl, ?int $notificationCount)
    {
        foreach ($this->drivers as $driver) {
            $tokens = (new TokenService())->getTokensForUser($user, $driver);
            if (empty($tokens)) {
                continue;
            }

            $driver->processCloudMessage($tokens, $title, $body, $url, $imageUrl, $notificationCount);
        }
    }

}
