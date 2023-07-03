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
                <?php else: ?>
                    <p class="alert alert-warning">
                        <strong>App Detection</strong> - Current Request: NO App Request Detected
                    </p>
                <?php endif; ?>

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
                $this->registerJsVar('registerMsg2', $message);
                ?>

                <p><code><?= $message; ?></code></p>

                <?= Html::a(
                    'Execute via JS Channel',
                    'javascript:alert("Send: "+ registerMsg2);window.flutterChannel.postMessage(registerMsg2);', ['class' => 'btn btn-primary pull-right']
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
                            <?= $fcm->token ?> <?= Html::a('Delete', ['index', 'deleteToken' => $fcm->id, 'confirm' => 'PWA: You may need to delete token from localStorage to trigger resave!']) ?>
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

                <p>This is usally </p>

                <?php
                $json = ['type' => 'registerFcmDevice', 'url' => Url::to(['/fcm-push/token/update'], true)];
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

                <?php
                $this->registerJsVar('registerMsg', $message);
                ?>

                <?= Html::a(
                    'Execute via JS Channel',
                    'javascript:alert("Send: "+ registerMsg);window.flutterChannel.postMessage(registerMsg);', ['class' => 'btn btn-primary pull-right']
                ) ?>
            </div>
        </div>

    </div>
</div>
