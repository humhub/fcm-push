<?php

namespace humhub\modules\fcmPush\controllers;

use humhub\modules\admin\components\Controller;
use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\fcmPush\Module;
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

}
