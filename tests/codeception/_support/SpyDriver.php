<?php

namespace fcmPush;

use humhub\modules\fcmPush\components\SendReport;
use humhub\modules\fcmPush\drivers\DriverInterface;
use humhub\modules\fcmPush\models\ConfigureForm;

/**
 * Push driver that records the messages instead of sending them.
 */
class SpyDriver implements DriverInterface
{
    public const SENDER_ID = 'spy-sender';

    public static array $messages = [];

    public function __construct(ConfigureForm $config)
    {
    }

    public function processCloudMessage(array $tokens, string $title, string $body, ?string $url, ?string $imageUrl, ?int $notificationCount): SendReport
    {
        static::$messages[] = compact('tokens', 'title', 'body', 'url', 'imageUrl', 'notificationCount');

        return new SendReport(SendReport::STATE_SUCCESS);
    }

    public function getSenderId(): string
    {
        return self::SENDER_ID;
    }

    public function isConfigured(): bool
    {
        return true;
    }
}
