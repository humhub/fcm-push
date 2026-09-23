<?php

use humhub\modules\fcmPush\models\FcmUser;
use humhub\widgets\bootstrap\Link;
use humhub\widgets\modal\Modal;
use humhub\widgets\modal\ModalButton;

/* @var FcmUser[] $tokens */
?>

<?php Modal::beginDialog([
    'title' => 'Registered FireBase Devices (Current User)',
    'footer' => ModalButton::cancel(),
]) ?>
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
                <?= Link::to('Delete')
                    ->link(['debug', 'deleteToken' => $fcm->id])
                    ->confirm('PWA: You may need to delete token from localStorage to trigger resave!')?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
<?php Modal::endDialog(); ?>
