<?php

namespace humhub\modules\fcmPush\driver;

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

class Fcm implements DriverInterface
{
    private ?Messaging $messaging = null;
    private ConfigureForm $config;

    public function __construct(ConfigureForm $config)
    {
        $this->config = $config;
    }

    public function processCloudMessage(array $tokens, string $title, string $body, ?string $url, ?string $imageUrl, ?int $notificationCount): SendReport
    {
        if ($this->messaging === null) {
            Module::registerAutoloader();

            $factory = (new Factory())->withServiceAccount($this->config->getJsonAsArray());
            $this->messaging = $factory->createMessaging();
        }

        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body, $imageUrl))
            ->withWebPushConfig(['fcm_options' => ['link' => $url]])
            ->withData(['url' => $url, 'notification_count' => $notificationCount]);

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
            foreach ($report->failures()->getItems() as $failure) {
                $failedTokens[] = $failure->target()->value();
            }
        }

        return new SendReport(SendReport::STATE_SUCCESS, $failedTokens);
    }

    public function getSenderId(): string
    {
        return $this->config->senderId;
    }

    public function isConfigured(): bool
    {
        return !empty($this->config->json) &&
            !empty($this->config->senderId) &&
            !empty($this->config->getJsonParam('project_id')) &&
            !empty($this->config->firebaseApiKey) &&
            !empty($this->config->firebaseAppId) &&
            !empty($this->config->firebaseVapidKey);
    }
}
