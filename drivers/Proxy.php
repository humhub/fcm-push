<?php

namespace humhub\modules\fcmPush\drivers;

use humhub\libs\HttpClient;
use humhub\modules\fcmPush\components\SendReport;
use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\fcmPush\Module;
use Yii;

/**
 * HumHub Push Proxy driver.
 *
 * Delivers notifications to the official HumHub community mobile app (iOS/Android)
 * by forwarding the message payload to the HumHub-managed relay service at
 * https://push.humhub.com. The relay service holds the Firebase credentials for
 * the community app and dispatches FCM messages on the operator's behalf.
 *
 * The operator only needs to register at push.humhub.com and obtain an API key —
 * no Firebase project setup is required for this driver.
 *
 * getSenderId() returns $module->humhubProxySenderId (hardcoded to the HumHub
 * community app's Firebase Sender ID) so that Proxy tokens are stored separately
 * from any custom Fcm tokens in the fcmpush_user table.
 */
class Proxy extends HttpClient implements DriverInterface, SilentMessageDriverInterface
{
    public $baseUrl = 'https://push.humhub.com/api/v1';

    public function __construct(private ConfigureForm $config)
    {
        parent::__construct();
    }

    public function createRequest()
    {
        $request = parent::createRequest();
        $request->addHeaders(['Authorization' => 'Bearer ' . $this->config->humhubApiKey]);

        return $request;
    }

    public function processCloudMessage(array $tokens, string $title, string $body, ?string $url, ?string $imageUrl, ?int $notificationCount, ?string $collapseKey = null, ?bool $highPriority = null): SendReport
    {
        return $this->sendCloudMessage(static::cloudMessageData($tokens, $title, $body, $url, $imageUrl, $notificationCount, $collapseKey, $highPriority));
    }

    /**
     * The request data of a visible message.
     *
     * The collapse key and the priority are sent as the optional fields `collapseKey` and
     * `priority` (`high` or `normal`) and only when given, so the request of a message without
     * them is unchanged. They are meant for the FCM options of the relayed message
     * (`android.collapse_key`/`priority`, `apns-collapse-id`/`apns-priority`); a relay version
     * without support for them ignores them.
     *
     * @since 2.4.0
     */
    protected static function cloudMessageData(array $tokens, string $title, string $body, ?string $url, ?string $imageUrl, ?int $notificationCount, ?string $collapseKey = null, ?bool $highPriority = null): array
    {
        $data = [
            'tokens' => $tokens,
            'title' => $title,
            'body' => $body,
            'iconUrl' => $imageUrl,
            'url' => $url,
            'notificationCount' => $notificationCount,
        ];
        if ($collapseKey !== null && $collapseKey !== '') {
            $data['collapseKey'] = $collapseKey;
        }
        if ($highPriority !== null) {
            $data['priority'] = $highPriority ? 'high' : 'normal';
        }

        return $data;
    }

    public function processSilentCloudMessage(array $tokens, ?int $notificationCount): SendReport
    {
        // A silent message carries no title/body, so the relay dispatches a data-only
        // message that only updates the app badge count without showing a notification.
        return $this->sendCloudMessage([
            'tokens' => $tokens,
            'notificationCount' => $notificationCount,
            'silent' => true,
        ]);
    }

    private function sendCloudMessage(array $data): SendReport
    {
        $response = $this->post('/push', $data)->send();

        if (!$response->isOk || empty($response->data['success'])) {
            Yii::error('Could not send request: ' . print_r($response->data, 1), 'fcm-push');
            return new SendReport(SendReport::STATE_ERROR);
        }

        return new SendReport(SendReport::STATE_SUCCESS);
    }

    public function isConfigured(): bool
    {
        return (!empty($this->config->humhubApiKey));
    }

    public function getSenderId(): string
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        return $module->humhubProxySenderId;
    }
}
