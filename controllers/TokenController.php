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
    public function actionUpdate()
    {
        $this->forcePostRequest();

        if (Yii::$app->user->isGuest) {
            Yii::$app->response->statusCode = 401;
            return $this->asJson(['success' => false]);
        }

        $driver = (new DriverService($this->module->getConfigureForm()))->getWebDriver();
        if (!$driver) {
            Yii::$app->response->statusCode = 400;
            return $this->asJson(['success' => false, 'message' => 'No push driver available!']);
        }

        $token = (string)Yii::$app->request->post('token');

        $tokenService = new TokenService();
        return $this->asJson([
            'success' => ($tokenService->storeTokenForUser(Yii::$app->user->getIdentity(), $driver, $token)),
        ]);
    }

}