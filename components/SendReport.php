<?php

namespace humhub\modules\fcmPush\components;

/**
 * Represents the result of a cloud message dispatch attempt.
 *
 * The Fcm driver populates $failedTokens with tokens that were rejected by
 * Firebase (e.g. expired / unregistered tokens). These are currently reported
 * but not automatically cleaned up — stale tokens remain in the database until
 * the user logs out or an admin removes them manually via the debug page.
 */
class SendReport
{
    public const STATE_SUCCESS = 1;
    public const STATE_ERROR = 2;

    public $failedTokens = [];

    public function __construct($state, $failedTokens = [])
    {

    }
}
