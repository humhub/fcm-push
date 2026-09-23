<?php

namespace humhub\modules\fcmPush\components;

/**
 * Represents the result of a cloud message dispatch attempt.
 *
 * The Fcm driver populates $failedTokens with tokens that were rejected by
 * Firebase (e.g. expired / unregistered tokens). MessagingService uses this
 * list to auto-delete stale tokens from the database after each send.
 */
class SendReport
{
    public const STATE_SUCCESS = 1;
    public const STATE_ERROR = 2;

    public function __construct(public readonly int $state, public array $failedTokens = [])
    {
    }
}
