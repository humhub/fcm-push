<?php


namespace humhub\modules\fcmPush\controllers;

use humhub\components\Controller;
use humhub\modules\fcmPush\services\TokenService;
use Yii;

class TokenController extends Controller
{
    public function actionUpdate()
    {
        $this->forcePostRequest();

        if (Yii::$app->user->isGuest) {
            Yii::$app->response->statusCode = 401;
            return $this->asJson(['success' => false]);
        }

        $token = (string)Yii::$app->request->post('token');

        $tokenService = new TokenService();
        return $this->asJson(['success' => ($tokenService->storeTokenForUser(Yii::$app->user->getIdentity(), $token))]);
    }

}