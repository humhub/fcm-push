<?php

use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\ui\icon\widgets\Icon;
use humhub\widgets\Button;
use humhub\modules\ui\form\widgets\ActiveForm;
use yii\helpers\Html;

/* @var $model ConfigureForm */
?>
<div class="panel panel-default">
    <div class="panel-heading"><?= Yii::t('FcmPushModule.base', '<strong>FireBase Messaging</strong> Configuration'); ?></div>
    <div class="panel-body">
        <?php $form = ActiveForm::begin(['id' => 'configure-form', 'enableClientValidation' => false, 'enableClientScript' => false]); ?>

        <h3><?= Yii::t('FcmPushModule.base', 'Link Redirection Service') ?></h3>
        <?= $form->field($model, 'enableEmailGoService')->checkbox()
            ->label(Yii::t('FcmPushModule.base', 'Enable Link Redirection Service. In order for links to open in the app on mobile devices, rather than in the mobile browser, all links (e.g. notification emails) need to be routed through the HumHub proxy server. (Experimental Features // <a href="{url}">Privacy Policy</a>)', [
                'url' => 'https://www.humhub.com/en/privacy/',
            ])) ?>

        <hr>

        <h3><?= Yii::t('FcmPushModule.base', 'Push Service (required for the mobile app) (Beta)') ?></h3>
        <p>
            <?= Yii::t('FcmPushModule.base', 'For HumHub mobile app users, push notifications can be sent via the HumHub push service. If you want to use this service, please enter your access key below.<br/>Please note, this push gateway is only available for the users of the official HumHub mobile app.') ?>
        </p>
        <?= Button::info('Push Service Registration')->link('https://push.humhub.com')->options(['target' => '_blank'])->loader(false) ?>
        <?= $form->field($model, 'humhubInstallId')->textInput(['disabled' => 'disabled']); ?>

        <?= $form->field($model, 'humhubApiKey')->textarea(['rows' => 2]); ?>
        <br/>

        <hr>

        <h3><?= Yii::t('FcmPushModule.base', 'Firebase Cloud Messaging (required for browser & PWA notifications)') ?></h3>
        <p>
            <?= Yii::t('FcmPushModule.base', 'To send Firebase push notifications with your own Firebase project, enter your access details here.') ?>
        </p>
        <?= Button::info('Installation documentation')->link('https://marketplace.humhub.com/module/fcm-push/installation')->options(['target' => '_blank'])->loader(false) ?>
        <?= $form->field($model, 'senderId') ?>
        <?= $form->field($model, 'firebaseApiKey') ?>
        <?= $form->field($model, 'firebaseAppId') ?>
        <?= $form->field($model, 'firebaseVapidKey') ?>
        <?= $form->field($model, 'json')->textarea(['rows' => 10]); ?>
        <br/>


        <?= $form->beginCollapsibleFields('Advanced Settings'); ?>
        <?= $form->field($model, 'disableAuthChoicesIos')->checkbox()
            ->label(Yii::t('FcmPushModule.base', 'Hide third-party login options for app users with iOS.')) ?>

        <?php if (!Yii::$app->urlManager->enablePrettyUrl) : ?>
        <div class="alert alert-warning">
            <?= Icon::get('warning') ?>
            <?= Yii::t('FcmPushModule.base', 'Please enable <a href="{url}" target="_blank">Pretty URLs</a> for proper working of the well-known files.', [
                'url' => 'https://docs.humhub.org/docs/admin/installation/#pretty-urls',
            ]) ?>
        </div>
        <?php endif; ?>

        <?= $form->field($model, 'fileAssetLinks')->textarea(['rows' => 10]) ?>
        <?= $form->field($model, 'fileAppleAssociation')->textarea(['rows' => 10]) ?>
        <?= $form->endCollapsibleFields(); ?>
        <br/>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('base', 'Save'), ['class' => 'btn btn-primary', 'data-ui-loader' => '']) ?>
        </div>

        <?php ActiveForm::end(); ?>

        <?= Html::a('Mobile App Debug', ['/fcm-push/mobile-app'], ['class' => 'pull-right']); ?>
    </div>
</div>
