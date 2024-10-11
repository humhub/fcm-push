<?php

namespace humhub\modules\fcmPush\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\components\Response;
use humhub\modules\fcmPush\Module;
use humhub\modules\fcmPush\services\DriverService;
use humhub\modules\fcmPush\services\TokenService;
use Yii;
use yii\web\HttpException;

/**
 * @property Module $module
 */
class TokenController extends Controller
{
    public $enableCsrfValidation = false;

    public $access = ControllerAccess::class;

    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $this->forcePostRequest();
        return parent::beforeAction($action);
    }

    public function actionUpdate()
    {
        $this->requireLogin();

        $driver = (new DriverService($this->module->getConfigureForm()))->getWebDriver();
        if (!$driver) {
            Yii::$app->response->statusCode = 400;
            return $this->asJson(['success' => false, 'message' => 'No push driver available!']);
        }

        return $this->asJson([
            'success' => (
                (new TokenService())->storeTokenForUser(
                    Yii::$app->user->getIdentity(),
                    $driver,
                    Yii::$app->request->post('token'),
                )
            ),
        ]);
    }

    public function actionUpdateMobileApp()
    {
        $this->requireLogin();

        $driver = (new DriverService($this->module->getConfigureForm()))->getMobileAppDriver();
        if (!$driver) {
            Yii::error('Could not update token for mobile app. No driver available.', 'fcm-push');

            Yii::$app->response->statusCode = 400;
            return $this->asJson(['success' => false, 'message' => 'No push driver available!']);
        }

        return $this->asJson([
            'success' => (
                (new TokenService())->storeTokenForUser(
                    Yii::$app->user->getIdentity(),
                    $driver,
                    Yii::$app->request->post('token'),
                )
            ),
        ]);
    }


    public function actionDeleteMobileApp()
    {
        $driver = (new DriverService($this->module->getConfigureForm()))->getMobileAppDriver();
        if (!$driver) {
            Yii::error('Could not delete token for mobile app. No driver available.', 'fcm-push');

            Yii::$app->response->statusCode = 400;
            return $this->asJson(['success' => false, 'message' => 'No push driver available!']);
        }

        if (empty(Yii::$app->request->post('token'))) {
            return $this->asJson(['success' => false, 'message' => 'No token given!']);
        }

        return $this->asJson([
            'success' => (
                (new TokenService())->deleteToken(
                    Yii::$app->request->post('token'),
                )
            ),
        ]);
    }

    private function requireLogin(): void
    {
        if (Yii::$app->user->isGuest) {
            throw new HttpException(401, 'Login required!');
        }
    }

}
