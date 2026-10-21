<?php

namespace humhub\modules\fcmPush\helpers;

/**
 * Helpers for log messages of the module.
 */
class LogHelper
{
    /**
     * Short, non-reversible identifier of a device token for log messages.
     *
     * A push token identifies a device (and so a person), the full value does not belong into the
     * log file. The identifier still allows to match the "send failure" and the "removing token"
     * lines of the same token.
     *
     * @since 2.4.0
     */
    public static function tokenId(string $token): string
    {
        return substr(sha1($token), 0, 8);
    }
}
