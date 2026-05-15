<?php

namespace humhub\modules\fcmPush\drivers;

use humhub\modules\fcmPush\components\SendReport;
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

    public function processCloudMessage(array $tokens, string $title, string $body, ?string $url, ?string $imageUrl, ?int $notificationCount): SendReport
    {
        if ($this->messaging === null) {
            Module::registerAutoloader();

            $factory = (new Factory())->withServiceAccount($this->config->getJsonAsArray());
            $this->messaging = $factory->createMessaging();
        }

        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body)) // $imageUrl is intentionally omitted — including it would show a duplicate logo on branded apps
            ->withWebPushConfig(['fcm_options' => ['link' => $url]])
            ->withData(['url' => $url, 'notification_count' => $notificationCount])
            ->withHighestPossiblePriority();

        try {
            $report = $this->messaging->sendMulticast($message, $tokens);
        } catch (MessagingException $e) {
            Yii::warning("Messaging Exception: " . print_r($e, 1), 'fcm-push');
            return new SendReport(SendReport::STATE_ERROR);
        } catch (FirebaseException $e) {
            Yii::warning("Firebase Exception: " . print_r($e, 1), 'fcm-push');
            return new SendReport(SendReport::STATE_ERROR);
        }

        $failedTokens = [];
        if ($report->hasFailures()) {
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
