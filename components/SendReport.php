<?php

namespace humhub\modules\fcmPush\components;

class SendReport
{
    public const STATE_SUCCESS = 1;
    public const STATE_ERROR = 2;

    public $failedTokens = [];

    public function __construct($state, $failedTokens = [])
    {

    }
}
