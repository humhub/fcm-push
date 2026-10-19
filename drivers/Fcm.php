<?php

namespace humhub\modules\fcmPush\drivers;

use humhub\modules\fcmPush\components\SendReport;
use humhub\modules\fcmPush\helpers\LogHelper;
use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\fcmPush\Module;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Yii;

/**
 * Direct Firebase Cloud Messaging driver.
 *
 * Used for:
 * - Browser / PWA notifications (web push via the Firebase JS SDK)
 * - Branded mobile apps that ship with the operator's own Firebase project
 *
 * Authentication is handled via a Google Service Account JSON file (stored in
 * ConfigureForm::$json). The kreait/firebase-php SDK is lazily autoloaded from
 * the module's own vendor directory to avoid conflicts with core Composer deps.
 *
 * isConfigured() requires the full set of Firebase credentials: service account JSON,
 * Sender ID, Web API Key, Web App ID, and VAPID key. All five must be non-empty.
 */
class Fcm implements DriverInterface
{
    private ?Messaging $messaging = null;

    public function __construct(private ConfigureForm $config)
    {
    }

    public function processCloudMessage(array $tokens, string $title, string $body, ?string $url, ?string $imageUrl, ?int $notificationCount, ?string $collapseKey = null, ?bool $highPriority = null): SendReport
    {
        Module::registerAutoloader();

        if ($this->messaging === null) {
            $factory = (new Factory())->withServiceAccount($this->config->getJsonAsArray());
            $this->messaging = $factory->createMessaging();
        }

        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body)) // $imageUrl is intentionally omitted — including it would show a duplicate logo on branded apps
            ->withAndroidConfig(static::androidConfig($collapseKey, $highPriority))
            ->withApnsConfig(static::apnsConfig($collapseKey, $highPriority))
            ->withWebPushConfig(static::webPushConfig($url, $collapseKey, $highPriority))
            ->withData(['url' => $url, 'notification_count' => $notificationCount]);

        try {
            $report = $this->messaging->sendMulticast($message, $tokens);
        } catch (MessagingException $e) {
            // Not the exception object: it carries the request, including the title, the text and the tokens
            Yii::warning('Messaging Exception ' . $e::class . ': ' . $e->getMessage(), 'fcm-push');
            return new SendReport(SendReport::STATE_ERROR);
        } catch (FirebaseException $e) {
            Yii::warning('Firebase Exception ' . $e::class . ': ' . $e->getMessage(), 'fcm-push');
            return new SendReport(SendReport::STATE_ERROR);
        }

        $failedTokens = [];
        if ($report->hasFailures()) {
            // Log every failure: sendMulticast() collects per-message errors instead of
            // throwing, so without this, non-token errors (permission denied, FCM API
            // disabled, quota exceeded, …) are dropped and the send looks successful.
            foreach ($report->failures()->getItems() as $failure) {
                $error = $failure->error();
                Yii::warning(
                    'FCM send failure for token ' . LogHelper::tokenId($failure->target()->value()) . ': '
                    . ($error?->getMessage() ?? 'unknown error'),
                    'fcm-push',
                );
            }

            // Only collect tokens that Firebase has permanently invalidated:
            //   - unknownTokens(): Firebase returned UNREGISTERED (app was uninstalled / token expired)
            //   - invalidTokens(): token is structurally malformed
            //
            // Transient errors (rate limit, server unavailable, device temporarily offline, …)
            // appear in failures() but NOT in these two lists — those tokens must NOT be deleted
            // because the device may be reachable again on the next send.
            $failedTokens = array_merge($report->unknownTokens(), $report->invalidTokens());
        }

        return new SendReport(SendReport::STATE_SUCCESS, $failedTokens);
    }

    /**
     * `collapse_key` (replaces a message still stored for an offline device) and the message
     * priority: `high` unless `$highPriority` is false.
     *
     * @since 2.4.0
     */
    protected static function androidConfig(?string $collapseKey, ?bool $highPriority): array
    {
        $config = ['priority' => $highPriority === false ? 'normal' : 'high'];
        if ($collapseKey !== null && $collapseKey !== '') {
            $config['collapse_key'] = $collapseKey;
        }

        return $config;
    }

    /**
     * `apns-collapse-id` (a newer notification replaces the displayed one, max. 64 bytes) and
     * `apns-priority`: 10 (immediately) unless `$highPriority` is false, then 5 (power considerations).
     *
     * @since 2.4.0
     */
    protected static function apnsConfig(?string $collapseKey, ?bool $highPriority): array
    {
        $headers = ['apns-priority' => $highPriority === false ? '5' : '10'];
        if ($collapseKey !== null && $collapseKey !== '') {
            $headers['apns-collapse-id'] = substr($collapseKey, 0, 64);
        }

        return ['headers' => $headers];
    }

    /**
     * The click link, the `Urgency` (`high` unless `$highPriority` is false, then `normal`) and the
     * `Topic` (replaces a message still pending in the push service; max. 32 characters of the
     * URL-safe base64 alphabet, so the collapse key is sanitized).
     *
     * @since 2.4.0
     */
    protected static function webPushConfig(?string $url, ?string $collapseKey, ?bool $highPriority): array
    {
        $headers = ['Urgency' => $highPriority === false ? 'normal' : 'high'];
        if ($collapseKey !== null && $collapseKey !== '') {
            $headers['Topic'] = substr(preg_replace('/[^A-Za-z0-9_-]/', '-', $collapseKey), 0, 32);
        }

        return ['fcm_options' => ['link' => $url], 'headers' => $headers];
    }

    public function getSenderId(): string
    {
        return $this->config->senderId;
    }

    public function isConfigured(): bool
    {
        return !empty($this->config->json)
            && !empty($this->config->senderId)
            && !empty($this->config->getJsonParam('project_id'))
            && !empty($this->config->firebaseApiKey)
            && !empty($this->config->firebaseAppId)
            && !empty($this->config->firebaseVapidKey);
    }
}
