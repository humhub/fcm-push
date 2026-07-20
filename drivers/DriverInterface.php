<?php

namespace humhub\modules\fcmPush\drivers;

use humhub\modules\fcmPush\components\SendReport;
use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\user\models\User;

interface DriverInterface
{
    public function __construct(ConfigureForm $config);

    public function processCloudMessage(array $tokens, string $title, string $body, ?string $url, ?string $imageUrl, ?int $notificationCount): SendReport;

    /**
     * Sends a silent (data-only) message that only carries the unread notification count,
     * without a visible notification. Used to keep the app badge count in sync.
     *
     * @since 2.2.9
     */
    public function processSilentCloudMessage(array $tokens, ?int $notificationCount): SendReport;

    public function getSenderId(): string;

    public function isConfigured(): bool;
}
