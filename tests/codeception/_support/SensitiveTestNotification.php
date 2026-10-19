<?php

namespace fcmPush;

use humhub\helpers\Html;
use humhub\modules\notification\components\BaseNotification;

/**
 * Notification whose text contains the name of the originator and (synthetic) confidential content.
 */
class SensitiveTestNotification extends BaseNotification
{
    public const SECRET_CONTENT = 'Confidential agenda item 4711';

    public $moduleId = 'fcm-push';
    public $requireSource = false;

    public function html()
    {
        return Html::encode($this->originator->displayName) . ' wrote: ' . self::SECRET_CONTENT;
    }

    public function getMailSubject()
    {
        return 'Subject: ' . self::SECRET_CONTENT;
    }
}
