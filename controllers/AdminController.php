<?php

namespace humhub\modules\fcmPush\controllers;

use humhub\modules\admin\components\Controller;
use humhub\modules\fcmPush\models\FcmUser;
use humhub\modules\fcmPush\Module;
use Yii;

/**
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

    public function actionDebug()
    {
        if ($tokenId = Yii::$app->request->get('deleteToken')) {
            $token = FcmUser::findOne(['id' => $tokenId]);
            if ($token->delete()) {
                $this->view->success('Token deleted!');
            } else {
                $this->view->warn('Token NOT deleted!');
            }
            return $this->redirect('index');
        }

        return $this->renderAjax('debug', [
            'tokens' => FcmUser::find()
                ->where(['user_id' => Yii::$app->user->id])
                ->orderBy('created_at DESC')
                ->all(),
        ]);
    }

}
