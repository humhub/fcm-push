<?php

namespace tests\codeception\unit;

use humhub\modules\fcmPush\drivers\Fcm;
use humhub\modules\fcmPush\models\ConfigureForm;
use tests\codeception\_support\HumHubDbTestCase;

/**
 * The platform options of a message of the {@see Fcm} driver: collapse key and priority.
 *
 * @since 2.4.0
 */
class FcmMessageOptionsTest extends HumHubDbTestCase
{
    public function testHighPriorityAndDefault()
    {
        foreach ([true, null] as $highPriority) {
            $this->assertSame(['priority' => 'high'], $this->probe()::android(null, $highPriority));
            $this->assertSame(['headers' => ['apns-priority' => '10']], $this->probe()::apns(null, $highPriority));
            $this->assertSame(
                ['fcm_options' => ['link' => 'https://example.com/n'], 'headers' => ['Urgency' => 'high']],
                $this->probe()::webPush('https://example.com/n', null, $highPriority),
            );
        }
    }

    public function testNormalPriority()
    {
        $this->assertSame('normal', $this->probe()::android(null, false)['priority']);
        $this->assertSame('5', $this->probe()::apns(null, false)['headers']['apns-priority']);
        $this->assertSame('normal', $this->probe()::webPush(null, null, false)['headers']['Urgency']);
    }

    public function testCollapseKey()
    {
        $this->assertSame('mobile:12', $this->probe()::android('mobile:12', true)['collapse_key']);
        $this->assertSame('mobile:12', $this->probe()::apns('mobile:12', true)['headers']['apns-collapse-id']);
        // Topic: URL-safe base64 characters only, at most 32
        $this->assertSame('mobile-12', $this->probe()::webPush(null, 'mobile:12', true)['headers']['Topic']);
        $this->assertSame(32, strlen($this->probe()::webPush(null, str_repeat('a', 40), true)['headers']['Topic']));
    }

    private function probe(): Fcm
    {
        // created at runtime: the module classes are autoloadable only once the application is up
        return new class (new ConfigureForm()) extends Fcm {
            public static function android(?string $collapseKey, ?bool $highPriority): array
            {
                return static::androidConfig($collapseKey, $highPriority);
            }

            public static function apns(?string $collapseKey, ?bool $highPriority): array
            {
                return static::apnsConfig($collapseKey, $highPriority);
            }

            public static function webPush(?string $url, ?string $collapseKey, ?bool $highPriority): array
            {
                return static::webPushConfig($url, $collapseKey, $highPriority);
            }
        };
    }
}
