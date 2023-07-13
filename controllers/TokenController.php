<?php


namespace humhub\modules\fcmPush\controllers;

use humhub\components\Controller;
use humhub\modules\fcmPush\Module;
use humhub\modules\fcmPush\services\DriverService;
use humhub\modules\fcmPush\services\TokenService;
use Yii;

/**
 * @property Module $module
 */
class TokenController extends Controller
{
    public $enableCsrfValidation = false;


    public function beforeAction($action)
    {
        $this->forcePostRequest();

        if (Yii::$app->user->isGuest) {
            Yii::$app->response->statusCode = 401;
            return false;
        }

        return parent::beforeAction($action);
    }

    public function actionUpdate()
    {
        $driver = (new DriverService($this->module->getConfigureForm()))->getWebDriver();
        if (!$driver) {
            Yii::$app->response->statusCode = 400;
            return $this->asJson(['success' => false, 'message' => 'No push driver available!']);
        }

        return $this->asJson([
            'success' => ((new TokenService())->storeTokenForUser(
                Yii::$app->user->getIdentity(), $driver, Yii::$app->request->post('token'))
            ),
        ]);
    }

    public function actionUpdateMobileApp()
    {
        $driver = (new DriverService($this->module->getConfigureForm()))->getMobileAppDriver();
        if (!$driver) {
            Yii::$app->response->statusCode = 400;
            return $this->asJson(['success' => false, 'message' => 'No push driver available!']);
        }

        return $this->asJson([
            'success' => ((new TokenService())->storeTokenForUser(
                Yii::$app->user->getIdentity(), $driver, Yii::$app->request->post('token'))
            ),
        ]);
    }


}