<?php

namespace fcmPush;

use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\fcmPush\models\FcmUser;
use humhub\modules\fcmPush\services\MessagingService;
use humhub\modules\user\models\User;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\helpers\Url;

/**
 * With the setting "Send a neutral text only" no names and no content leave the server: neither
 * for notifications nor for texts that other modules hand over.
 *
 * @since 2.3.2
 */
class NeutralPushTextTest extends HumHubDbTestCase
{
    protected $fixtureConfig = ['default'];

    private int $recordId = 0;

    protected function _before()
    {
        parent::_before();

        SpyDriver::$messages = [];
        FcmUser::deleteAll();

        $token = new FcmUser();
        $token->user_id = $this->recipient()->id;
        $token->token = 'synthetic-test-token';
        $token->sender_id = SpyDriver::SENDER_ID;
        $this->assertTrue($token->save());
    }

    public function testNotificationTextIsSentByDefault()
    {
        $message = $this->sendNotification(false);

        $this->assertStringContainsString(SensitiveTestNotification::SECRET_CONTENT, $message['body']);
        $this->assertStringContainsString($this->originator()->displayName, $message['body']);
    }

    public function testNotificationIsNeutralWithTheSetting()
    {
        $message = $this->sendNotification(true);

        $this->assertSame(Yii::$app->name, $message['title']);
        $this->assertSame('You have a new notification.', $message['body']);
        $this->assertNoneOf($message, [SensitiveTestNotification::SECRET_CONTENT, $this->originator()->displayName]);
        // the link to the notification is still sent: it holds an id only
        $this->assertSame(Url::to(['/notification/entry', 'id' => $this->recordId], true), $message['url']);
    }

    public function testTextsOfOtherModulesAreNeutralToo()
    {
        $service = $this->service(true);
        $service->processMessage($this->recipient(), 'Conversation with ' . $this->originator()->displayName, SensitiveTestNotification::SECRET_CONTENT, 'https://example.com/mail/mail/index', null);

        $this->assertCount(1, SpyDriver::$messages);
        $message = SpyDriver::$messages[0];
        $this->assertSame(Yii::$app->name, $message['title']);
        $this->assertSame('You have a new notification.', $message['body']);
        $this->assertSame('https://example.com/mail/mail/index', $message['url']);
        $this->assertNoneOf($message, [SensitiveTestNotification::SECRET_CONTENT, $this->originator()->displayName]);
    }

    public function testTextsOfOtherModulesAreUnchangedByDefault()
    {
        $this->service(false)->processMessage($this->recipient(), 'Title', 'Body', null, null);

        $this->assertSame('Title', SpyDriver::$messages[0]['title']);
        $this->assertSame('Body', SpyDriver::$messages[0]['body']);
    }

    public function testSettingIsSavedAndLoaded()
    {
        $form = new ConfigureForm();
        $form->loadSettings();
        $this->assertFalse($form->neutralPushText);

        $form->load(['ConfigureForm' => ['neutralPushText' => '1']]);
        $this->assertTrue($form->validate(['neutralPushText']));
        $form->saveSettings();

        $loaded = new ConfigureForm();
        $loaded->loadSettings();
        $this->assertTrue($loaded->neutralPushText);

        $loaded->neutralPushText = false;
        $loaded->saveSettings();
        $loaded->loadSettings();
        $this->assertFalse($loaded->neutralPushText);
    }

    private function sendNotification(bool $neutral): array
    {
        $notification = SensitiveTestNotification::instance()->from($this->originator());
        $this->assertTrue($notification->saveRecord($this->recipient()));
        $this->recordId = $notification->record->id;

        $this->service($neutral)->processNotification($notification, $this->recipient());

        $this->assertCount(1, SpyDriver::$messages);

        return SpyDriver::$messages[0];
    }

    private function service(bool $neutral): MessagingService
    {
        $config = new ConfigureForm();
        $config->neutralPushText = $neutral;

        $service = new MessagingService($config);
        (new \ReflectionProperty(MessagingService::class, 'drivers'))->setValue($service, [new SpyDriver($config)]);

        return $service;
    }

    private function recipient(): User
    {
        return User::findOne(['username' => 'User1']);
    }

    private function originator(): User
    {
        return User::findOne(['username' => 'User2']);
    }

    /**
     * Everything that leaves the server must not contain the given texts.
     *
     * @param string[] $forbidden
     */
    private function assertNoneOf(array $message, array $forbidden): void
    {
        $everything = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        foreach ($forbidden as $text) {
            $this->assertStringNotContainsString($text, $everything);
        }
    }
}
