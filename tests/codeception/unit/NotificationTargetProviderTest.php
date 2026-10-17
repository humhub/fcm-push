<?php

namespace tests\codeception\unit;

use humhub\components\Event;
use humhub\modules\fcmPush\components\NotificationTargetProvider;
use humhub\modules\fcmPush\events\NotificationCountEvent;
use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\fcmPush\Module;
use humhub\modules\fcmPush\services\MessagingService;
use humhub\modules\notification\components\NotificationManager;
use humhub\modules\notification\models\Notification;
use humhub\modules\notification\services\NotificationListService;
use humhub\modules\notification\targets\MobileTarget;
use humhub\modules\notification\targets\MobileTargetProvider;
use humhub\modules\notification\tests\codeception\unit\notifications\TestDirectNotification;
use humhub\modules\post\models\Post;
use humhub\modules\user\models\User;
use ReflectionProperty;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\helpers\Url;

/**
 * The bridge between the core notification delivery ({@see MobileTargetProvider::deliver()}) and
 * the push drivers. The drivers are not called: {@see MessagingService::processMessage()} is
 * replaced by a spy.
 *
 * @since 2.4.0
 */
class NotificationTargetProviderTest extends HumHubDbTestCase
{
    protected $fixtureConfig = ['default'];

    protected function _before()
    {
        parent::_before();

        // The Proxy driver counts as configured with an API key
        $module = $this->module();
        $module->settings->set('humhubApiKey', 'test-api-key');
        foreach (['configForm', 'driverService'] as $property) {
            (new ReflectionProperty(Module::class, $property))->setValue($module, null);
        }
    }

    protected function _after()
    {
        Yii::$container->clear(MobileTargetProvider::class);
        Yii::$container->clear(MessagingService::class);
        Event::off(MessagingService::class, MessagingService::EVENT_NOTIFICATION_COUNT);
        $this->module()->settings->delete('humhubApiKey');

        parent::_after();
    }

    public function testIsActiveWithAConfiguredDriver()
    {
        $this->assertTrue((new NotificationTargetProvider())->isActive());
        $this->assertTrue((new NotificationTargetProvider())->isActive(User::findOne(['id' => 1])));
    }

    public function testDispatchedNotificationReachesTheDriversWithPushBodyAndEntryUrl()
    {
        $spy = $this->messagingServiceSpy();
        Yii::$container->set(MessagingService::class, $spy);
        Yii::$container->set(MobileTargetProvider::class, NotificationTargetProvider::class);

        // a fresh manager, the application's has instantiated its targets already
        Yii::$app->set('notification', ['class' => NotificationManager::class, 'targets' => Yii::$app->notification->targets]);
        $this->assertContains(MobileTarget::ID, array_map(fn($target) => $target->id, Yii::$app->notification->getTargets(User::findOne(['id' => 1]))));

        TestDirectNotification::send(1, source: Post::findOne(['id' => 2]), originator: User::findOne(['id' => 2]));

        $record = Notification::findOne(['class' => TestDirectNotification::class, 'user_id' => 1]);
        $this->assertNotNull($record);

        $this->assertCount(1, $spy->calls);
        $call = $spy->calls[0];
        $this->assertSame(1, (int)$call['user']->id);
        $this->assertSame(Yii::$app->name, $call['title']);
        $this->assertSame(NotificationManager::load($record)->asPush(), $call['body']);
        $this->assertStringContainsString('mentioned you', $call['body']);
        $this->assertSame(Url::to(['/notification/entry', 'id' => $record->id], true), $call['url']);
        $this->assertSame('mobile:1:' . (int)$record->grouping_key, $call['collapseKey']);
        $this->assertTrue($call['highPriority']);
    }

    public function testNotificationCountIsTheUnseenListCountPlusModuleCounts()
    {
        TestDirectNotification::send(1, source: Post::findOne(['id' => 2]), originator: User::findOne(['id' => 2]));

        $user = User::findOne(['id' => 1]);
        $unseen = NotificationListService::unseenCount($user);
        $this->assertGreaterThan(0, $unseen);

        $service = new MessagingService(new ConfigureForm());
        $this->assertSame($unseen, $service->getNotificationCount($user));

        Event::on(MessagingService::class, MessagingService::EVENT_NOTIFICATION_COUNT, function (NotificationCountEvent $event) {
            $event->count += 3;
        });
        $this->assertSame($unseen + 3, $service->getNotificationCount($user));
    }

    private function module(): Module
    {
        return Yii::$app->getModule('fcm-push');
    }

    private function messagingServiceSpy(): MessagingService
    {
        return new class (new ConfigureForm()) extends MessagingService {
            public array $calls = [];

            public function processMessage(User $user, string $title, string $body, ?string $url, ?string $imageUrl, ?int $notificationCount = null, ?string $collapseKey = null, ?bool $highPriority = null)
            {
                $this->calls[] = compact('user', 'title', 'body', 'url', 'imageUrl', 'collapseKey', 'highPriority');
            }
        };
    }
}
