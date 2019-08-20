<?php


namespace humhub\modules\fcmPush\components;


use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\fcmPush\models\FcmUser;
use humhub\modules\notification\components\BaseNotification;
use humhub\modules\user\models\User;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\httpclient\Client;


/**
 * Class FcmApi
 *
 *
 * @see https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages
 * @package humhub\modules\fcmPush\components
 */
class FcmApi extends Client
{

    public $baseUrl = 'https://fcm.googleapis.com/fcm/';


    public function createRequest()
    {
        $request = parent::createRequest();
        $request->addHeaders(['Authorization' => 'key=' . ConfigureForm::getInstance()->serverKey]);
        return $request;
    }


    public function sendNotification(BaseNotification $baseNotification, User $user)
    {
        $tokens = [];
        foreach (FcmUser::findAll(['user_id' => $user->id]) as $fcmUser) {
            $tokens[] = $fcmUser->token;
        }

        if (count($tokens) === 0) {
            return false;
        }

        $data = [
            "notification" => [
                "title" => Html::encode(Yii::$app->name),
                "body" => $baseNotification->text(),
                "icon" => "alarm.png",
                "click_action" => Url::to(['/notification/entry', 'id' => $baseNotification->record->id], true)
            ],
            "to" => $tokens[0]
        ];

        $response = $this->post('send', $data)->send();
        if (!$response->isOk) {
            throw new \yii\base\Exception("Response is not ok!" . print_r($response->data, 1));
        }

        return true;
    }
}