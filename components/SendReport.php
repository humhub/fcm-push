<?php

namespace humhub\modules\fcmPush\components;

class SendReport
{
    const STATE_SUCCESS = 1;
    const STATE_ERROR = 2;

    public $failedTokens = [];

    public function __construct($state, $failedTokens = [])
    {

    }
}