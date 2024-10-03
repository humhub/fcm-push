<?php

namespace humhub\modules\fcmPush\driver;

use humhub\modules\fcmPush\components\SendReport;
use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\user\models\User;

interface DriverInterface
{
    public function __construct(ConfigureForm $config);

    public function processCloudMessage(array $tokens, string $title, string $body, ?string $url, ?string $imageUrl, ?int $notificationCount): SendReport;

    public function getSenderId(): string;

    public function isConfigured(): bool;
}
