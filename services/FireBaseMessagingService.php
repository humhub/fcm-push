<?php

namespace humhub\modules\fcmPush\services;

use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\fcmPush\Module;
use humhub\modules\notification\components\BaseNotification;
use humhub\modules\user\models\User;
use humhub\modules\web\pwa\widgets\SiteIcon;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

class FireBaseMessagingService
{

    private Messaging $messaging;

    public function __construct(ConfigureForm $config)
    {
        Module::registerAutoloader();

        //Yii::getAlias('@fcm-push/firebase.json')
        $factory = (new Factory)->withServiceAccount($config->getJsonAsArray());
        $this->messaging = $factory->createMessaging();
    }


    public function processNotification(BaseNotification $baseNotification, User $user)
    {
        $this->processCloudMessage(
            $user,
            Yii::$app->name,
            $baseNotification->text(),
            Url::to(['/notification/entry', 'id' => $baseNotification->record->id], true),
            SiteIcon::getUrl(180),
        );

        return true;
    }

    public function processCloudMessage(User $user, $title, $body, $url = "", $imageUrl = "")
    {
        if (empty($url)) {
            $url = Url::to(['/'], true);
        }

        $message = CloudMessage::new()
            ->withNotification(Notification::create('Title', 'Body'))
            ->withWebPushConfig(['fcm_options' => ['link' => $url]]);

        $tokens = (new TokenService())->getTokensForUser($user);
        try {
            $sendReport = $this->messaging->sendMulticast($message, $tokens);
        } catch (MessagingException $e) {
            Yii::warning("Messaging Exception: " . print_r($e, 1), 'fcm-push');
            return false;
        } catch (FirebaseException $e) {
            Yii::warning("Firebase Exception: " . print_r($e, 1), 'fcm-push');
            return false;
        }
        Yii::warning('Success: SendReport: ' . print_r($sendReport, 1), 'fcm-push');
    }
}