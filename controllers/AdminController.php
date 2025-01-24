<?php

namespace humhub\modules\fcmPush\controllers;

use humhub\modules\admin\components\Controller;
use humhub\modules\admin\notifications\NewVersionAvailable;
use humhub\modules\fcmPush\models\FcmUser;
use humhub\modules\fcmPush\Module;
use humhub\modules\user\models\User;
use Yii;

/**
 *
 * @property Module $module
 */
class AdminController extends Controller
{
    public function actionIndex()
    {

        $model = $this->module->getConfigureForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->saveSettings()) {
            $this->view->saved();
            return $this->redirect(['index']);
        }

        return $this->render('index', ['model' => $model]);
    }

    /**
     * @return string
     */
    public function actionMobileApp()
    {
        if (Yii::$app->request->get('triggerNotification') == 1) {

            /** @var User $user */
            $user = Yii::$app->user->getIdentity();

            $updateNotification = new NewVersionAvailable();
            $updateNotification->sendBulk(User::find()->where(['user.id' => $user->id]));
            $this->view->setStatusMessage('success', 'Notification queued!');
            return $this->redirect('mobile-app');
        }

        if (Yii::$app->request->get('deleteToken') != "") {
            $t = FcmUser::findOne(['id' => Yii::$app->request->get('deleteToken')]);
            if ($t->delete() !== false) {
                $this->view->setStatusMessage('success', 'Token deleted!');
                return $this->redirect('mobile-app');
            }

            $this->view->setStatusMessage('warning', 'Token NOT deleted!');
            return $this->redirect('mobile-app');
        }

        return $this->render('mobile-app');
    }

}
