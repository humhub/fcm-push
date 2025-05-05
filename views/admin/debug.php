<?php

use humhub\modules\fcmPush\models\FcmUser;
use humhub\widgets\Button;
use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;

/* @var FcmUser[] $tokens */
?>

<?php ModalDialog::begin(['header' => 'Registered FireBase Devices (Current User)']) ?>
<div class="modal-body">
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
                <?= Button::asLink('Delete')
                    ->link(['debug', 'deleteToken' => $fcm->id])
                    ->confirm('PWA: You may need to delete token from localStorage to trigger resave!')?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
<div class="modal-footer">
    <?= ModalButton::cancel() ?>
</div>
<?php ModalDialog::end() ?>
