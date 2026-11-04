<?php

namespace tests\codeception\unit;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use humhub\modules\fcmPush\drivers\Fcm;
use humhub\modules\fcmPush\helpers\LogHelper;
use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\fcmPush\Module;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport as FirebaseSendReport;
use Kreait\Firebase\Exception\Messaging\ApiConnectionFailed;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * The log of the drivers must not contain the text of the push message or the device tokens.
 *
 * @since 2.4.0
 */
class DriverLogTest extends HumHubDbTestCase
{
    private const TITLE = 'Synthetic title 4711';
    private const BODY = 'Synthetic confidential text 0815';
    private const TOKEN = 'synthetic-device-token-0123456789abcdef';

    public function testMessagingExceptionIsLoggedWithoutRequestData()
    {
        Module::registerAutoloader();

        // like the SDK: the exception wraps the HTTP exception, which carries the request
        $request = new Request('POST', 'https://fcm.googleapis.com/v1/projects/x/messages:send', [], json_encode([
            'message' => ['token' => self::TOKEN, 'notification' => ['title' => self::TITLE, 'body' => self::BODY]],
        ]));
        $previous = new RequestException('Connection reset', $request);
        $exception = new ApiConnectionFailed($previous->getMessage(), 0, $previous);
        $this->assertInstanceOf(MessagingException::class, $exception);
        $this->assertStringContainsString(self::BODY, (string)$exception->getPrevious()->getRequest()->getBody(), 'precondition: the exception carries the message');

        $messaging = $this->createMock(Messaging::class);
        $messaging->method('sendMulticast')->willThrowException($exception);

        $this->sendWith($messaging);

        $log = $this->logText();
        $this->assertStringContainsString('Messaging Exception', $log);
        $this->assertStringContainsString('Connection reset', $log);
        $this->assertNoPayload($log);
    }

    public function testSendFailureIsLoggedWithTokenIdOnly()
    {
        Module::registerAutoloader();

        $failure = FirebaseSendReport::failure(
            MessageTarget::with(MessageTarget::TOKEN, self::TOKEN),
            new \RuntimeException('Third party auth error'),
        );
        $messaging = $this->createMock(Messaging::class);
        $messaging->method('sendMulticast')->willReturn(MulticastSendReport::withItems([$failure]));

        $this->sendWith($messaging);

        $log = $this->logText();
        $this->assertStringContainsString('Third party auth error', $log);
        $this->assertStringContainsString(LogHelper::tokenId(self::TOKEN), $log);
        $this->assertNoPayload($log);
    }

    public function testTokenIdIsStableAndShort()
    {
        $this->assertSame(LogHelper::tokenId(self::TOKEN), LogHelper::tokenId(self::TOKEN));
        $this->assertSame(8, strlen(LogHelper::tokenId(self::TOKEN)));
        $this->assertNotSame(LogHelper::tokenId(self::TOKEN), LogHelper::tokenId(self::TOKEN . 'x'));
    }

    private function sendWith(Messaging $messaging): void
    {
        $driver = new Fcm(new ConfigureForm());
        (new \ReflectionProperty(Fcm::class, 'messaging'))->setValue($driver, $messaging);

        Yii::getLogger()->getAndClearLog();
        $driver->processCloudMessage([self::TOKEN], self::TITLE, self::BODY, 'https://example.com/n', null, 1);
    }

    private function logText(): string
    {
        // the logger of the test application keeps the last messages, see Codeception\Lib\Connector\Yii2\Logger
        return Yii::getLogger()->getAndClearLog();
    }

    private function assertNoPayload(string $log): void
    {
        foreach ([self::TITLE, self::BODY, self::TOKEN] as $secret) {
            $this->assertStringNotContainsString($secret, $log);
        }
    }
}
