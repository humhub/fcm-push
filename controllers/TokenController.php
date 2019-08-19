<?php


namespace humhub\modules\fcmPush\controllers;

use humhub\components\Controller;
use humhub\modules\fcmPush\models\FcmUser;
use Yii;

class TokenController extends Controller
{
    /**
     * @inheritdoc
     */
    public function getAccessRules()
    {
        return [
            ['login']
        ];
    }

    public function actionUpdate()
    {
        $this->forcePostRequest();

        $token = (string) Yii::$app->request->post('token');

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