<?php
/* @var $this \humhub\modules\ui\view\components\View */

/* @var $model \humhub\modules\fcmPush\models\ConfigureForm */

use humhub\widgets\Button;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

?>
<div class="panel panel-default">
    <div class="panel-heading"><?= Yii::t('FcmPushModule.base', '<strong>FireBase Messaging</strong> Configuration'); ?></div>
    <div class="panel-body">
        <?php $form = ActiveForm::begin(['id' => 'configure-form', 'enableClientValidation' => false, 'enableClientScript' => false]); ?>

        <h3>Push Service (required for the mobile app) (Beta)</h3>
        <p>
            For HumHub mobile app users, push notifications can be sent via the HumHub push service.
            If you want to use this service, please enter your access key below.<br/>
            Please note, this push gateway is only available for the users of the official HumHub mobile app.
        </p>
        <?= Button::info('Push Service Registration')->link('https://push.humhub.com')->options(['target' => '_blank'])->loader(false) ?>
        <?= $form->field($model, 'humhubInstallId')->textInput(['disabled' => 'disabled']); ?>

        <?= $form->field($model, 'humhubApiKey')->textarea(['rows' => 2]); ?>
        <br/>

        <hr>

        <h3>Firebase Cloud Messaging (required for browser & PWA notifications)</h3>
        <p>
            To send Firebase push notifications with your own Firebase project, enter your access details here.
        </p>
        <?= Button::info('Installation documentation')->link('https://marketplace.humhub.com/module/fcm-push/installation')->options(['target' => '_blank'])->loader(false) ?>
        <?= $form->field($model, 'senderId'); ?>
        <?= $form->field($model, 'json')->textarea(['rows' => 10]); ?>
        <?php if (!empty($model->serverKey)): ?>
            <?= $form->field($model, 'serverKey')->textInput(); ?>
        <?php endif; ?>

        <br/>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('base', 'Save'), ['class' => 'btn btn-primary', 'data-ui-loader' => '']) ?>
        </div>

        <?php ActiveForm::end(); ?>

        <?= Html::a('Mobile App Debug', ['/fcm-push/mobile-app'], ['class' => 'pull-right']); ?>
    </div>
</div>

