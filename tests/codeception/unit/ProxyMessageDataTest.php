<?php

namespace tests\codeception\unit;

use humhub\modules\fcmPush\drivers\Proxy;
use humhub\modules\fcmPush\models\ConfigureForm;
use tests\codeception\_support\HumHubDbTestCase;

/**
 * The request data of a visible message of the {@see Proxy} driver, incl. the optional collapse
 * key and priority.
 *
 * @since 2.4.0
 */
class ProxyMessageDataTest extends HumHubDbTestCase
{
    public function testWithoutCollapseKeyAndPriority()
    {
        $this->assertSame([
            'tokens' => ['t1', 't2'],
            'title' => 'HumHub',
            'body' => 'Body',
            'iconUrl' => 'https://example.com/icon.png',
            'url' => 'https://example.com/n',
            'notificationCount' => 3,
        ], $this->probe()::data(['t1', 't2'], 'HumHub', 'Body', 'https://example.com/n', 'https://example.com/icon.png', 3, null, null));

        $data = $this->probe()::data(['t1'], 'HumHub', 'Body', null, null, null, '', null);
        $this->assertArrayNotHasKey('collapseKey', $data);
        $this->assertArrayNotHasKey('priority', $data);
    }

    public function testCollapseKeyAndPriority()
    {
        $data = $this->probe()::data(['t1'], 'HumHub', 'Body', null, null, 1, 'mobile:12', true);
        $this->assertSame('mobile:12', $data['collapseKey']);
        $this->assertSame('high', $data['priority']);

        $this->assertSame('normal', $this->probe()::data(['t1'], 'HumHub', 'Body', null, null, 1, null, false)['priority']);
    }

    private function probe(): Proxy
    {
        // created at runtime: the module classes are autoloadable only once the application is up
        return new class (new ConfigureForm()) extends Proxy {
            public static function data(array $tokens, string $title, string $body, ?string $url, ?string $imageUrl, ?int $notificationCount, ?string $collapseKey, ?bool $highPriority): array
            {
                return static::cloudMessageData($tokens, $title, $body, $url, $imageUrl, $notificationCount, $collapseKey, $highPriority);
            }
        };
    }
}
