<?php

/* @var $this \humhub\modules\ui\view\components\View */

use humhub\libs\Html;
use humhub\modules\fcmPush\helpers\MobileAppHelper;
use humhub\modules\fcmPush\models\FcmUser;
use yii\helpers\Json;
use yii\helpers\Url;


?>


<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong>Mobile App</strong> Debug Page
            </div>
            <div class="panel-body">

                <?php if (MobileAppHelper::isAppRequest()): ?>
                    <p class="alert alert-success">
                        <strong>App Detection</strong> - Current Request: Is App Request
                    </p>

                    <?php if (MobileAppHelper::isAppWithCustomFcm()): ?>
                        <p class="alert alert-success">
                            <strong>FCM Detection</strong> - App is using custom Firebase
                        </p>
                    <?php else: ?>
                        <p class="alert alert-warning">
                            <strong>FCM Detection</strong> - App is using Proxy Firebase Service
                        </p>
                    <?php endif; ?>

                <?php else: ?>
                    <p class="alert alert-warning">
                        <strong>App Detection</strong> - Current Request: NO App Request Detected
                    </p>
                <?php endif; ?>

                <?= Html::a('Show Opener', '#', ['class' => 'btn btn-default postFlutterMsgLink', 'data-message' => Json::encode(['type' => 'showOpener'])]); ?>
                <?= Html::a('Hide Opener', '#', ['class' => 'btn btn-default postFlutterMsgLink', 'data-message' => Json::encode(['type' => 'hideOpener'])]); ?>
                <?= Html::a('Open this page as POST Request', ['index'], ['data-method' => 'POST', 'class' => 'btn btn-default']); ?>

            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-body">
                <h4>Test Push Notification</h4>

                <p>Make sure the <code>Mobile</code> checkbox is enabled for <a
                            href="<?= Url::to(['/notification/user']); ?>">
                        Administrative Notifications!</a>. It may take a few minutes.
                </p>

                <?= Html::a('Trigger "HumHub Update" notification', ['index', 'triggerNotification' => 1], ['class' => 'btn btn-primary pull-right']) ?>

            </div>
        </div>


        <div class="panel panel-default">
            <div class="panel-body">
                <h4>Test Update Notification Count</h4>

                <p>Set Notification Count to a number between 100 and 200.</p>

                <?php
                $message = Json::encode(['type' => 'updateNotificationCount', 'count' => rand(100, 200)]);
                ?>

                <p><code><?= $message; ?></code></p>

                <?= Html::a(
                    'Execute via JS Channel',
                    '#',
                    ['class' => 'btn btn-primary pull-right postFlutterMsgLink', 'data-message' => $message]
                ) ?>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-body">
                <h4>Registered FireBase Devices (Current User)</h4>

                <?php
                $tokens = FcmUser::findAll(['user_id' => Yii::$app->user->id]);
                ?>

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

                            <?= Html::a('Delete', ['index', 'deleteToken' => $fcm->id, 'confirm' => 'PWA: You may need to delete token from localStorage to trigger resave!']) ?>
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

                <?php
                $json = ['type' => 'registerFcmDevice', 'url' => Url::to(['/fcm-push/token/update-mobile-app'], true)];
                $message = Json::encode($json);
                ?>

                <p><code><?= $message; ?></code></p>


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

                <?= Html::a(
                    'Execute via JS Channel',
                    '#',
                    ['class' => 'btn btn-primary pull-right postFlutterMsgLink', 'data-message' => $message]
                ) ?>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-body">

                <h4>Send `unregisterFcmDevice` message </h4>

                <?php
                $json = ['type' => 'unregisterFcmDevice', 'url' => Url::to(['/fcm-push/token/delete-mobile-app'], true)];
                $message = Json::encode($json);
                ?>

                <p><code><?= $message; ?></code></p>


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

                <?= Html::a(
                    'Execute via JS Channel',
                    '#',
                    ['class' => 'btn btn-primary pull-right postFlutterMsgLink', 'data-message' => $message]
                ) ?>
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