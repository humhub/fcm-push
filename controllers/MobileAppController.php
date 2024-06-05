<?php

namespace humhub\modules\fcmPush\controllers;


use humhub\modules\admin\components\Controller;
use humhub\modules\admin\notifications\NewVersionAvailable;
use humhub\modules\fcmPush\models\FcmUser;
use humhub\modules\fcmPush\Module;
use humhub\modules\user\models\User;
use Yii;
use yii\web\Response;

class MobileAppController extends Controller
{
    /**
     * @inheritdoc
     * @var Module $module
     */
    public $module;

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

    public function actionStatus()
    {
        if (!$this->module->isActivated) {
            return $this->returnStatus(404,'FCM Module is not installed');
        }

        if (!$this->module->getDriverService()->hasConfiguredDriver()) {
            return $this->returnStatus(501,'Push Proxy is not configured');
        }

        return $this->returnStatus(200,'OK, Push (Proxy) is correctly configured');
    }

    private function returnStatus(int $code, string $message): Response
    {
        Yii::$app->response->statusCode = $code;

        return $this->asJson([
            'code' => $code,
            'message' => $message,
        ]);
    }

}
