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
            ->withNotification(Notification::create($title, $body))
            ->withWebPushConfig(['fcm_options' => ['link' => $url]]);

        $tokens = (new TokenService())->getTokensForUser($user);
        try {
            $report = $this->messaging->sendMulticast($message, $tokens);
        } catch (MessagingException $e) {
            Yii::warning("Messaging Exception: " . print_r($e, 1), 'fcm-push');
            return false;
        } catch (FirebaseException $e) {
            Yii::warning("Firebase Exception: " . print_r($e, 1), 'fcm-push');
            return false;
        }

        //Yii::warning('Send: ' . $report->successes()->count(), 'fcm-push');

        if ($report->hasFailures()) {
            foreach ($report->failures()->getItems() as $failure) {
                // TODO: Delete Token?
                //Yii::warning('Error Send: ' . $failure->error()->getMessage(), 'fcm-push');
            }
        }

        /*
        // The following methods return arrays with registration token strings
        $successfulTargets = $report->validTokens(); // string[]

        // Unknown tokens are tokens that are valid but not know to the currently
        // used Firebase project. This can, for example, happen when you are
        // sending from a project on a staging environment to tokens in a
        // production environment
        $unknownTargets = $report->unknownTokens(); // string[]

        // Invalid (=malformed) tokens
        $invalidTargets = $report->invalidTokens(); // string[]

        Yii::warning('Success: SendReport: ' . print_r($sendReport, 1), 'fcm-push');
        */
    }
}