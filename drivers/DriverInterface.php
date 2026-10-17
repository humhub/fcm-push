<?php

namespace humhub\modules\fcmPush\drivers;

use humhub\modules\fcmPush\components\SendReport;
use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\user\models\User;

interface DriverInterface
{
    public function __construct(ConfigureForm $config);

    /**
     * Sends a visible push message to the given tokens.
     *
     * @param string|null $collapseKey a newer message with the same key replaces an older one on the
     *        device or in the push service, null for none (since 2.4.0)
     * @param bool|null $highPriority whether the message is delivered immediately (true) or may be
     *        delayed by the device's power saving (false); null for the driver's default (since 2.4.0)
     */
    public function processCloudMessage(array $tokens, string $title, string $body, ?string $url, ?string $imageUrl, ?int $notificationCount, ?string $collapseKey = null, ?bool $highPriority = null): SendReport;

    public function getSenderId(): string;

    public function isConfigured(): bool;
}
