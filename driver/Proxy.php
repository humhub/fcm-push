<?php

namespace humhub\modules\fcmPush\driver;

use humhub\modules\fcmPush\components\SendReport;
use humhub\modules\fcmPush\Module;
use Yii;
use yii\httpclient\Client;
use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\user\models\User;

class Proxy extends Client implements DriverInterface
{
    public $baseUrl = 'https://push.humhub.com/api';

    private ConfigureForm $config;

    public function __construct(ConfigureForm $config)
    {
        $this->config = $config;
    }

    public function createRequest()
    {
        $request = parent::createRequest();
        $request->addHeaders(['Authorization' => 'key=' . $this->config->humhubApiKey]);
        $request->setFormat(Client::FORMAT_JSON);

        return $request;
    }

    public function processCloudMessage(array $tokens, string $title, string $body, ?string $url, ?string $imageUrl): SendReport
    {
        $data = [
            "notification" => [
                "title" => $title,
                "body" => $body,
                "icon" => $imageUrl,
                "click_action" => $url
            ],
            "data" => [
                "title" => $title,
                "body" => $body,
                "icon" => $imageUrl,
                "url" => $url
            ],
            "registration_ids" => $tokens
        ];

        $response = $this->post('send', $data)->send();
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