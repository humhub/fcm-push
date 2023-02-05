<?php

namespace humhub\modules\fcmPush\services;

use humhub\modules\fcmPush\driver\DriverInterface;
use humhub\modules\fcmPush\driver\Fcm;
use humhub\modules\fcmPush\driver\FcmLegacy;
use humhub\modules\fcmPush\driver\Proxy;
use humhub\modules\fcmPush\models\ConfigureForm;

class DriverService
{
    private ConfigureForm $config;

    private array $configuredDrivers;

    public function __construct(ConfigureForm $config)
    {
        $this->config = $config;
        $this->initDrivers();
    }

    private function initDrivers()
    {
        $this->configuredDrivers = [];

        $proxy = new Proxy($this->config);
        if ($proxy->isConfigured()) {
            $this->configuredDrivers[] = $proxy;
        }

        $fcm = new Fcm($this->config);
        if ($fcm->isConfigured()) {
            $this->configuredDrivers[] = $fcm;
        } else {
            $fcmLegacy = new FcmLegacy($this->config);
            if ($fcmLegacy->isConfigured()) {
                $this->configuredDrivers[] = $fcmLegacy;
            }
        }
    }


    /**
     * There may be several Firebase drivers at the same time. e.g.
     *
     * - Fcm or FcmLegacy: For PWA/Web Notification
     * - HumHubProxy: For the official Mobile Apps
     *
     * @return DriverInterface[]
     */
    public function getConfiguredDrivers(): array
    {
        return $this->configuredDrivers;
    }

    public function getWebDriver(): ?DriverInterface
    {
        // If Fcm driver is available use it
        foreach ($this->configuredDrivers as $driver) {
            if ($driver instanceof Fcm || $driver instanceof FcmLegacy) {
                return $driver;
            }
        }

        // Fallback to Proxy driver
        foreach ($this->configuredDrivers as $driver) {
            if ($driver instanceof Proxy) {
                return $driver;
            }
        }

        return null;
    }

    public function hasConfiguredDriver(): bool
    {
        return (!empty($this->configuredDrivers));
    }

}