<?php

namespace humhub\modules\fcmPush\commands;

use humhub\modules\fcmPush\Module;
use humhub\modules\fcmPush\services\MessagingService;
use humhub\modules\user\models\User;
use Yii;
use yii\console\ExitCode;

class SendController extends \yii\console\Controller
{
    public function actionSendToUser($userId, $title, $message)
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        $user = User::findOne(['id' => $userId]);
        if ($user === null) {
            $this->stderr("User with ID $userId not found!\n");
            return ExitCode::DATAERR;
        }

        $firebaseService = new MessagingService($module->getConfigureForm());

        $firebaseService->processMessage(
            $user,
            $title,
            $message,
            null,
            null,
        );

        return ExitCode::OK;
    }
}
