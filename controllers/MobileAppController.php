<?php

namespace humhub\modules\fcmPush\controllers;

use humhub\modules\admin\components\Controller;
use humhub\modules\admin\notifications\NewVersionAvailable;
use humhub\modules\fcmPush\models\FcmUser;
use humhub\modules\user\models\User;
use Yii;

class MobileAppController extends Controller
{
    /**
     * Renders the index view for the module
     *
     * @return string
     */
    public function actionIndex()
    {

        if (Yii::$app->request->get('triggerNotification') == 1) {

            /** @var User $user */
            $user = Yii::$app->user->getIdentity();

            $updateNotification = new NewVersionAvailable();
            $updateNotification->sendBulk(User::find()->where(['user.id' => $user->id]));
            $this->view->setStatusMessage('success', 'Notification queued!');
            return $this->redirect('index');
        }

        if (Yii::$app->request->get('deleteToken') != "") {
            $t = FcmUser::findOne(['id' => Yii::$app->request->get('deleteToken')]);
            if ($t->delete() !== false) {
                $this->view->setStatusMessage('success', 'Token deleted!');
                return $this->redirect('index');
            } else {
                $this->view->setStatusMessage('warning', 'Token NOT deleted!');
                return $this->redirect('index');
            }
        }

        return $this->render('index');
    }

}
