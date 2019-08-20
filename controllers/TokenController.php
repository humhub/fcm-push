<?php


namespace humhub\modules\fcmPush\controllers;

use humhub\components\Controller;
use humhub\modules\fcmPush\models\FcmUser;
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

        $fcmUser = FcmUser::findOne(['token' => $token]);
        if ($fcmUser !== null && $fcmUser->user_id !== Yii::$app->user->id) {
            $fcmUser->delete();
            $fcmUser = null;
        }

        if ($fcmUser === null) {
            $fcmUser = new FcmUser();
            $fcmUser->user_id = Yii::$app->user->id;
            $fcmUser->token = $token;
        }


        return $this->asJson(['success' => ($fcmUser->save())]);
    }

}