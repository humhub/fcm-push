<?php


namespace humhub\modules\fcmPush\components;


use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\fcmPush\models\FcmUser;
use humhub\modules\notification\components\BaseNotification;
use humhub\modules\user\models\User;
use humhub\modules\web\pwa\widgets\SiteIcon;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\httpclient\Client;


/**
 * Class FcmApi
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
        $request->setFormat(Client::FORMAT_JSON);

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
                "icon" => SiteIcon::getUrl(180),
                "click_action" => Url::to(['/notification/entry', 'id' => $baseNotification->record->id], true)
            ],
            "data" => [
                "title" => Html::encode(Yii::$app->name),
                "body" => $baseNotification->text(),
                "icon" => SiteIcon::getUrl(180),
                "url" => Url::to(['/notification/entry', 'id' => $baseNotification->record->id], true)
            ],
            "registration_ids" => $tokens
        ];

        $response = $this->post('send', $data)->send();
        if (!$response->isOk || empty($response->data['success'])) {
            throw new \yii\base\Exception("Response is not ok!" . print_r($response->data, 1));
        }

        return true;
    }
}