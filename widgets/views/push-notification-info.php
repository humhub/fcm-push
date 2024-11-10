<?php

use yii\helpers\Html;
use humhub\widgets\Button;
use humhub\modules\fcmPush\assets\FcmPushAsset;

/* @var $this \humhub\modules\ui\view\components\View */
/* @var $tokenUpdateUrl string */
/* @var $senderId string */

$this->registerJsConfig('firebase', [
    'tokenUpdateUrl' => $tokenUpdateUrl,
    'senderId' => $senderId,
    'statusTexts' => [
        'granted' => Yii::t('FcmPushModule.base', 'Granted: Push Notifications are active on this browser.<br>You can disable it in browser settings for this site.'),
        'denied' => Yii::t('FcmPushModule.base', 'Denied: You have blocked Push Notifications.<br>You can enable it in browser settings for this site.'),
        'default' => Yii::t('FcmPushModule.base', 'Default: Push Notifications are not yet enabled.') . '<br>' .
            Button::primary(Yii::t('FcmPushModule.base', 'Click here to enable'))
                ->icon('fa-unlock')
                ->id('enablePushBtn')
                ->sm()
                ->loader(false),
        'not-supported' => Yii::t('FcmPushModule.base', 'Not Supported: This browser does not support notifications.'),
    ]
]);

FcmPushAsset::register($this);
?>
<div id="push-notification-info" class="panel panel-default">
    <div class="panel-heading">
        <?= Yii::t('FcmPushModule.base', 'Push Notifications') ?>
    </div>
    <div class="panel-body" id="pushNotificationStatus">
    </div>
</div>
