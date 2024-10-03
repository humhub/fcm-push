<?php

namespace humhub\modules\fcmPush\commands;

use humhub\modules\fcmPush\Module;
use humhub\modules\fcmPush\services\MessagingService;
use humhub\modules\user\models\User;
use Yii;

class SendController extends \yii\console\Controller
{
    public function actionSendToUser($userId, $title, $message)
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        $firebaseService = new MessagingService($module->getConfigureForm());

        $firebaseService->processMessage(
            User::findOne(['id' => $userId]),
            $title,
            $message,
            null,
            null,
            null,
        );
    }
}
