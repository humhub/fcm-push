<?php

namespace humhub\modules\fcmPush\driver;

use humhub\modules\fcmPush\components\SendReport;
use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\fcmPush\Module;
use Yii;
use yii\httpclient\Client;

class Proxy extends Client implements DriverInterface
{
    public $baseUrl = 'https://push.humhub.com/api/v1';

    private ConfigureForm $config;

    public function __construct(ConfigureForm $config)
    {
        $this->config = $config;
    }

    public function createRequest()
    {
        $request = parent::createRequest();
        $request->addHeaders(['Authorization' => 'Bearer ' . $this->config->humhubApiKey]);

        return $request;
    }

    public function processCloudMessage(array $tokens, string $title, string $body, ?string $url, ?string $imageUrl, ?int $notificationCount): SendReport
    {
        $data = [
            'tokens' => $tokens,
            'title' => $title,
            'body' => $body,
            'iconUrl' => $imageUrl,
            'url' => $url,
            'notificationCount' => $notificationCount,
        ];

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
