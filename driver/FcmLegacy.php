<?php

namespace humhub\modules\fcmPush\driver;

use humhub\modules\fcmPush\components\SendReport;
use humhub\modules\web\pwa\widgets\SiteIcon;
use Yii;
use yii\httpclient\Client;
use humhub\modules\fcmPush\models\ConfigureForm;

class FcmLegacy extends Client implements DriverInterface
{
    public $baseUrl = 'https://fcm.googleapis.com/fcm/';

    private ConfigureForm $config;

    public function __construct(ConfigureForm $config)
    {
        $this->config = $config;
    }

    public function createRequest()
    {
        $request = parent::createRequest();
        $request->addHeaders(['Authorization' => 'key=' . $this->config->serverKey]);
        $request->setFormat(Client::FORMAT_JSON);

        return $request;
    }

    public function processCloudMessage(array $tokens, string $title, string $body, ?string $url, ?string $imageUrl, ?int $notificationCount): SendReport
    {
        $data = [
            "notification" => [
                "title" => $title,
                "body" => $body,
                "icon" => SiteIcon::getUrl(180),
                "click_action" => $url,
            ],
            "data" => [
                "title" => $title,
                "body" => $body,
                "icon" => SiteIcon::getUrl(180),
                "url" => $url,
            ],
            "registration_ids" => $tokens,
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
        return (!empty($this->config->serverKey) && !empty($this->config->senderId));
    }

    public function getSenderId(): string
    {
        return $this->config->senderId;
    }
}
