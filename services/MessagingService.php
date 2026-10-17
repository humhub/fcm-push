<?php

namespace humhub\modules\fcmPush\services;

use humhub\components\Event;
use humhub\modules\fcmPush\components\SendReport;
use humhub\modules\fcmPush\drivers\DriverInterface;
use humhub\modules\fcmPush\drivers\SilentMessageDriverInterface;
use humhub\modules\fcmPush\events\NotificationCountEvent;
use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\notification\services\NotificationListService;
use humhub\modules\notification\targets\DeliveryBatch;
use humhub\modules\user\models\User;
use Yii;

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

    /**
     * Sends the push message of a notification delivery batch (one or several notifications of
     * one recipient) to the recipient's devices.
     *
     * @since 2.4.0
     */
    public function processBatch(DeliveryBatch $batch): void
    {
        // Nothing meaningful to push, e.g. when the source record of the notification was deleted
        $body = $batch->getPushBody();
        if ($body === '') {
            return;
        }

        $this->processMessage(
            $batch->recipient,
            Yii::$app->name,
            $body,
            $batch->getUrl(),
            Yii::$app->img->icon->getUrl(['square' => 180]),
            null,
            $batch->getCollapseKey(),
            $batch->isHighPriority(),
        );
    }

    /**
     * @param int|null $notificationCount deprecated since 2.2.9, will be removed in a future version.
     *        The value is ignored — the count is now calculated by {@see getNotificationCount()}.
     * @param string|null $collapseKey a newer message with the same key replaces an older one (since 2.4.0)
     * @param bool|null $highPriority whether to deliver immediately; null for the driver's default,
     *        which is the high priority (since 2.4.0)
     */
    public function processMessage(User $user, string $title, string $body, ?string $url, ?string $imageUrl, ?int $notificationCount = null, ?string $collapseKey = null, ?bool $highPriority = null)
    {
        $tokenService = new TokenService();
        $notificationCount = $this->getNotificationCount($user);

        foreach ($this->drivers as $driver) {
            $tokens = $tokenService->getTokensForUser($user, $driver);
            if (empty($tokens)) {
                continue;
            }

            try {
                $report = $driver->processCloudMessage($tokens, $title, $body, $url, $imageUrl, $notificationCount, $collapseKey, $highPriority);
            } catch (\Throwable $e) {
                Yii::error('Could not send push message with driver ' . $driver::class . ': ' . $e->getMessage(), 'fcm-push');
                continue;
            }

            $this->removeFailedTokens($tokenService, $report);
        }
    }

    /**
     * Sends a silent (data-only) push notification carrying the current unread notification
     * count to the user's registered mobile app devices, so the app can update its badge
     * count without displaying a visible notification.
     *
     * Only drivers implementing {@see SilentMessageDriverInterface} are targeted. For now that
     * is the Proxy driver (HumHub community mobile apps) only. The Fcm driver is intentionally
     * excluded because it serves both branded native apps and PWA web tokens without a way to
     * tell them apart (no platform flag on the token):
     *  - PWA: the service worker has no background badge handler, and iOS Safari revokes push
     *    subscriptions when silent pushes repeatedly arrive without showing a notification, which
     *    would break the iOS PWA push delivery (see #98).
     *  - Native iOS via direct FCM would additionally require an APNs config (`content-available`,
     *    `aps.badge`) to be delivered in the background.
     * The Proxy relay handles these platform specifics (APNs badge) on its side.
     *
     * @since 2.2.9
     */
    public function sendSilentUnreadNotificationCount(User $user): void
    {
        $tokenService = new TokenService();
        $notificationCount = $this->getNotificationCount($user);

        foreach ($this->drivers as $driver) {
            if (!$driver instanceof SilentMessageDriverInterface) {
                continue;
            }

            $tokens = $tokenService->getTokensForUser($user, $driver);
            if (empty($tokens)) {
                continue;
            }

            try {
                $report = $driver->processSilentCloudMessage($tokens, $notificationCount);
            } catch (\Throwable $e) {
                Yii::error('Could not send silent push message with driver ' . $driver::class . ': ' . $e->getMessage(), 'fcm-push');
                continue;
            }

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
     * The base is the user's number of unseen entries of the notification list (a group counts
     * once, {@see NotificationListService::unseenCount()}); {@see self::EVENT_NOTIFICATION_COUNT}
     * lets other modules add their own counts.
     *
     * @since 2.2.9
     */
    public function getNotificationCount(User $user): int
    {
        $event = new NotificationCountEvent(['user' => $user]);
        $event->count = NotificationListService::unseenCount($user);

        Event::trigger($this, self::EVENT_NOTIFICATION_COUNT, $event);

        return $event->count;
    }

}
