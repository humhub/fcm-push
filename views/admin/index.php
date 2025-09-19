<?php

use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\form\ActiveForm;
use humhub\widgets\modal\ModalButton;

/* @var $model ConfigureForm */
?>
<div class="panel panel-default">
    <div class="panel-heading"><?= Yii::t('FcmPushModule.base', '<strong>FireBase Messaging</strong> Configuration'); ?></div>
    <div class="panel-body">
        <?php $form = ActiveForm::begin(['id' => 'configure-form', 'enableClientValidation' => false, 'enableClientScript' => false]); ?>

        <h3><?= Yii::t('FcmPushModule.base', 'Push Service (required for the mobile app) (Beta)') ?></h3>
        <p>
            <?= Yii::t('FcmPushModule.base', 'For HumHub mobile app users, push notifications can be sent via the HumHub push service. If you want to use this service, please enter your access key below.<br/>Please note, this push gateway is only available for the users of the official HumHub mobile app.') ?>
        </p>
        <?= Button::accent('Push Service Registration')->link('https://push.humhub.com')->options(['target' => '_blank'])->loader(false) ?>
        <?= $form->field($model, 'humhubInstallId')->textInput(['disabled' => 'disabled']) ?>
        <?= $form->field($model, 'humhubApiKey')->textarea(['rows' => 2]) ?>
        <br/>

        <hr>

        <h3><?= Yii::t('FcmPushModule.base', 'Firebase Cloud Messaging (required for browser & PWA notifications)') ?></h3>
        <p>
            <?= Yii::t('FcmPushModule.base', 'To send Firebase push notifications with your own Firebase project, enter your access details here.') ?>
        </p>
        <?= Button::accent('Installation documentation')->link('https://marketplace.humhub.com/module/fcm-push/installation')->options(['target' => '_blank'])->loader(false) ?>
        <?= $form->field($model, 'senderId') ?>
        <?= $form->field($model, 'firebaseApiKey') ?>
        <?= $form->field($model, 'firebaseAppId') ?>
        <?= $form->field($model, 'firebaseVapidKey') ?>
        <?= $form->field($model, 'json')->textarea(['rows' => 10]); ?>
        <br/>

        <div class="form-group">
            <?= Button::save()->submit() ?>
            <?= ModalButton::defaultType('Debug')
                ->load(['/fcm-push/admin/debug'])
                ->icon('bug')
                ->right() ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
