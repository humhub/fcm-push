<?php
/* @var $this \humhub\modules\ui\view\components\View */

/* @var $model \humhub\modules\fcmPush\models\ConfigureForm */

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

?>
<div class="panel panel-default">
    <div class="panel-heading"><?= Yii::t('FcmPushModule.base', '<strong>FireBase Messaging</strong> Configuration'); ?></div>
    <div class="panel-body">
        <?php $form = ActiveForm::begin(['id' => 'configure-form', 'enableClientValidation' => false, 'enableClientScript' => false]); ?>

        <h3>Push Service (Beta)</h3>
        <p>
            For HumHub mobile app users or (for Web/PWA users) when no own Firebase account is provided,
            push notifications can be sent via the HumHub push service via Firebase. If you want to use this service,
            please enter your access key below.

        <ul>
            <li><a href="https://push.humhub.com">Push Service Registration</a></li>
        </ul>
        </p>
        <?= $form->field($model, 'humhubInstallId')->textInput(['disabled' => 'disabled']); ?>

        <?= $form->field($model, 'humhubApiKey')->textarea(['rows' => 2]); ?>
        <br/>

        <hr>

        <h3>Firebase Cloud Messaging</h3>
        <p>
            To send Firebase push notifications with your own Firebase project, enter your access details here.

        <ul>
            <li><a href="https://marketplace.humhub.com/module/fcm-push/installation">Installation </a></li>
        </ul>
        </p>
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

    </div>
</div>

