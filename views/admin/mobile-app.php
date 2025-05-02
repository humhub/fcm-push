<?php

use humhub\libs\Html;
use humhub\modules\fcmPush\models\FcmUser;

/* @var FcmUser[] $tokens */
?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
                <h4>Registered FireBase Devices (Current User)</h4>
                <?php if (count($tokens) === 0): ?>
                    <p class="alert alert-danger">
                        No registered Firebase Tokens for the current user!
                    </p>
                <?php else : ?>
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
