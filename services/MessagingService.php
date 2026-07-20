<?php

namespace humhub\modules\fcmPush\services;

use humhub\components\Event;
use humhub\modules\fcmPush\components\SendReport;
use humhub\modules\fcmPush\drivers\DriverInterface;
use humhub\modules\fcmPush\events\NotificationCountEvent;
use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\notification\components\BaseNotification;
use humhub\modules\notification\models\Notification as NotificationHumHub;
use humhub\modules\user\models\User;
use Yii;
use yii\helpers\Url;

class MessagingService
{
    /**
     * @event NotificationCountEvent an event raised when the push notification badge count is
     * calculated, allowing other modules to add their own counts via `$event->count`.
     * @since 2.2.9
     */
    public const EVENT_NOTIFICATION_COUNT = 'pushNotificationCount';

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
            Yii::$app->img->icon->getUrl(['square' => 180]),
        );
    }

    /**
     * @param int|null $notificationCount deprecated since 2.2.9, will be removed in a future version.
     *        The value is ignored — the count is now calculated by {@see getNotificationCount()}.
     */
    public function processMessage(User $user, string $title, string $body, ?string $url, ?string $imageUrl, ?int $notificationCount = null)
    {
        $tokenService = new TokenService();
        $notificationCount = $this->getNotificationCount($user);

        foreach ($this->drivers as $driver) {
            $tokens = $tokenService->getTokensForUser($user, $driver);
            if (empty($tokens)) {
                continue;
            }

            $report = $driver->processCloudMessage($tokens, $title, $body, $url, $imageUrl, $notificationCount);
            $this->removeFailedTokens($tokenService, $report);
        }
    }

    /**
     * Sends a silent (data-only) push notification carrying the current unread notification
     * count to the user's registered devices, so the mobile app (Proxy driver) and PWA app
     * (Fcm driver) can update their badge count without displaying a visible notification.
     *
     * @since 2.2.9
     */
    public function sendSilentUnreadNotificationCount(User $user): void
    {
        $tokenService = new TokenService();
        $notificationCount = $this->getNotificationCount($user);

        foreach ($this->drivers as $driver) {
            $tokens = $tokenService->getTokensForUser($user, $driver);
            if (empty($tokens)) {
                continue;
            }

            $report = $driver->processSilentCloudMessage($tokens, $notificationCount);
            $this->removeFailedTokens($tokenService, $report);
        }
    }

    /**
     * Removes tokens that Firebase rejected (e.g. from an uninstalled / reinstalled app).
     * This prevents stale tokens from accumulating and blocking future deliveries.
     */
    private function removeFailedTokens(TokenService $tokenService, SendReport $report): void
    {
        foreach ($report->failedTokens as $failedToken) {
            Yii::warning("Removing failed/unregistered FCM token: $failedToken", 'fcm-push');
            $tokenService->deleteToken($failedToken);
        }
    }

    /**
     * Calculates the push notification badge count for the given user.
     *
     * Triggers {@see self::EVENT_NOTIFICATION_COUNT} so other modules can add their own counts.
     *
     * @since 2.2.9
     */
    public function getNotificationCount(User $user): int
    {
        $event = new NotificationCountEvent(['user' => $user]);
        $event->count = (int)NotificationHumHub::findUnseen($user)->count();

        Event::trigger($this, self::EVENT_NOTIFICATION_COUNT, $event);

        return $event->count;
    }

}
