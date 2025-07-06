<?php

use humhub\components\View;
use humhub\helpers\DeviceDetectorHelper;
use humhub\helpers\Html;
use humhub\modules\fcmPush\models\FcmUser;
use humhub\widgets\bootstrap\Button;
use yii\helpers\Json;
use yii\helpers\Url;

/* @var $this View */
?>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= Button::back(['/fcm-push/admin/index'])->sm() ?>
                <strong>Mobile App</strong> Debug Page
            </div>

            <div class="panel-body">

                <?php if (DeviceDetectorHelper::isAppRequest()): ?>
                    <p class="alert alert-success">
                        <strong>App Detection</strong> - Current Request: Is App Request
                    </p>

                    <?php if (DeviceDetectorHelper::isAppWithCustomFcm()): ?>
                        <p class="alert alert-success">
                            <strong>FCM Detection</strong> - App is using custom Firebase
                        </p>
                    <?php else: ?>
                        <p class="alert alert-success">
                            <strong>FCM Detection</strong> - App is using Proxy Firebase Service
                        </p>
                    <?php endif; ?>

                <?php else: ?>
                    <p class="alert alert-warning">
                        <strong>App Detection</strong> - Current Request: NO App Request Detected
                    </p>
                <?php endif; ?>

                <?= Html::a('Show Opener', '#', ['class' => 'btn btn-light postFlutterMsgLink', 'data-message' => Json::encode(['type' => 'showOpener'])]) ?>
                <?= Html::a('Hide Opener', '#', ['class' => 'btn btn-light postFlutterMsgLink', 'data-message' => Json::encode(['type' => 'hideOpener'])]) ?>
                <?= Html::a('Open this page as POST Request', ['mobile-app'], ['data-method' => 'POST', 'class' => 'btn btn-light']) ?>
                <?= Html::a('Open native console', '#', ['class' => 'btn btn-light postFlutterMsgLink', 'data-message' => Json::encode(['type' => 'openNativeConsole'])]) ?>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-body">
                <h4>Test Push Notification</h4>

                <p>Make sure the <code>Mobile</code> checkbox is enabled for <a
                        href="<?= Url::to(['/notification/user']); ?>">
                        Administrative Notifications!</a>. It may take a few minutes.
                </p>

                <div class="clearfix">
                    <?= Button::primary('Trigger "HumHub Update" notification')
                        ->link(['mobile-app', 'triggerNotification' => 1])
                        ->right() ?>
                </div>

            </div>
        </div>


        <div class="panel panel-default">
            <div class="panel-body">
                <h4>Test Update Notification Count</h4>

                <p>Set Notification Count to a number between 100 and 200.</p>

                <?php $message = Json::encode(['type' => 'updateNotificationCount', 'count' => rand(100, 200)]); ?>

                <p><code><?= $message ?></code></p>

                <div class="clearfix">
                    <?= Html::a(
                        'Execute via JS Channel',
                        '#',
                        ['class' => 'btn btn-primary float-end postFlutterMsgLink', 'data-message' => $message],
                    ) ?>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-body">
                <h4>Registered FireBase Devices (Current User)</h4>

                <?php $tokens = FcmUser::find()->where(['user_id' => Yii::$app->user->id])->orderBy('created_at DESC')->all(); ?>

                <?php if (count($tokens) === 0): ?>
                    <p class="alert alert-danger">
                        No registered Firebase Tokens for the current user!
                    </p>

                <?php endif; ?>

                <ul>
                    <?php foreach ($tokens as $fcm): ?>
                        <li>
                            <?= substr($fcm->token, 0, 7) ?>
                            ...
                            <?= substr($fcm->token, -7) ?>

                            &middot;
                            <?= $fcm->sender_id ?>

                            &middot;
                            <?= Yii::$app->formatter->asDatetime($fcm->created_at, 'short') ?>

                            &middot;
                            <?= Html::a('Delete', ['mobile-app', 'deleteToken' => $fcm->id, 'confirm' => 'PWA: You may need to delete token from localStorage to trigger resave!']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-body">
                <h4>Headers</h4>
                <pre>
                        <?php print_r($_SERVER); ?>
                    </pre>

            </div>
        </div>


        <div class="panel panel-default">
            <div class="panel-body">

                <h4>Send `registerFcmDevice` message </h4>

                <?php $json = ['type' => 'registerFcmDevice', 'url' => Url::to(['/fcm-push/token/update-mobile-app'], true)]; ?>
                <?php $message = Json::encode($json); ?>

                <p><code><?= $message ?></code></p>


                <p>
                    The POST to given URL request must contain a `token` field in the payload.
                </p>
                <hr>

                <p>HTTP Return Codes for given URL:</p>

                <ul>
                    <li>201 - Token saved</li>
                    <li>200 - Token already saved</li>

                    <li>404 - No valid Method POST Request</li>
                    <li>400 - No `token` in payload</li>
                </ul>

                <div class="clearfix">
                    <?= Html::a(
                        'Execute via JS Channel',
                        '#',
                        ['class' => 'btn btn-primary float-end postFlutterMsgLink', 'data-message' => $message],
                    ) ?>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-body">

                <h4>Send `unregisterFcmDevice` message </h4>

                <?php $json = ['type' => 'unregisterFcmDevice', 'url' => Url::to(['/fcm-push/token/delete-mobile-app'], true)]; ?>
                <?php $message = Json::encode($json); ?>

                <p><code><?= $message ?></code></p>


                <p>
                    The POST to given URL request must contain a `token` field in the payload.
                </p>
                <hr>

                <p>HTTP Return Codes for given URL:</p>

                <ul>
                    <li>201 - Token saved</li>
                    <li>200 - Token already saved</li>

                    <li>404 - No valid Method POST Request</li>
                    <li>400 - No `token` in payload</li>
                </ul>

                <div class="clearfix">
                    <?= Html::a(
                        'Execute via JS Channel',
                        '#',
                        ['class' => 'btn btn-primary float-end postFlutterMsgLink', 'data-message' => $message],
                    ) ?>
                </div>
            </div>
        </div>

    </div>
</div>


<script <?= Html::nonce() ?>>
    $('.postFlutterMsgLink').on('click', function (evt) {
        var message = $(evt.target).data('message');
        if (window.flutterChannel) {
            try {
                window.flutterChannel.postMessage(JSON.stringify(message))
            } catch (err) {
                alert("Flutter Channel Error: " + err)
            }
            alert("Message sent! Message: " + JSON.stringify(message));
        } else {
            alert("Could not send message! Message: " + JSON.stringify(message));
        }
    });
</script>
