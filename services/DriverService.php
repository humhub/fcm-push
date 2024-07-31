<?php

namespace humhub\modules\fcmPush\services;

use humhub\modules\fcmPush\driver\DriverInterface;
use humhub\modules\fcmPush\driver\Fcm;
use humhub\modules\fcmPush\driver\FcmLegacy;
use humhub\modules\fcmPush\driver\Proxy;
use humhub\modules\fcmPush\models\ConfigureForm;
use humhub\modules\ui\helpers\DeviceDetectorHelper;

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

        // Do not allow Proxy for non App usage
        // return $this->getConfiguredDriverByType(Proxy::class);

        return null;
    }

    public function getMobileAppDriver(): ?DriverInterface
    {
        if (DeviceDetectorHelper::isAppWithCustomFcm()) {
            return $this->getConfiguredDriverByType(Fcm::class);
        }

        return $this->getConfiguredDriverByType(Proxy::class);
    }

    public function hasConfiguredWebDriver(): bool
    {
        return ($this->getWebDriver() !== null);
    }

    public function hasConfiguredDriver(): bool
    {
        return (!empty($this->configuredDrivers));
    }


    private function getConfiguredDriverByType(string $class): ?DriverInterface
    {
        foreach ($this->configuredDrivers as $driver) {
            if ($driver instanceof $class) {
                return $driver;
            }
        }

        return null;
    }


}
