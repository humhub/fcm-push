<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\fcmPush\components;

use humhub\components\mail\Message;
use humhub\modules\fcmPush\Module;
use Yii;

/**
 * Message
 *
 * @author Luke
 */
class MailerMessage extends Message
{
    protected ?Module $module = null;

    public function init()
    {
        parent::init();
        $this->module = Yii::$app->getModule('fcm-push');
    }

    /**
     * @inheritdoc
     */
    public function setHtmlBody($html): Message
    {
        return Message::setHtmlBody($this->module->getGoService()->processLinks($html));
    }

    /**
     * @inheritdoc
     */
    public function setTextBody($text): Message
    {
        return Message::setTextBody($this->module->getGoService()->processUrls($text));
    }
}
