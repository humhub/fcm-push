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

    public function __construct(ConfigureForm $config)
    {
        $this->config = $config;
    }


    /**
     * There may be several Firebase drivers at the same time. e.g.
     *
     * - Fcm or FcmLegacy: For PWA/Web Notification
     * - HumHubProxy: For the official Mobile Apps
     *
     * @return DriverInterface[]
     */
    public function getDrivers(): array
    {
        $drivers = [];

        $proxy = new Proxy($this->config);
        if ($proxy->isConfigured()) {
            $drivers[] = $proxy;
        }

        $fcm = new Fcm($this->config);
        if ($fcm->isConfigured()) {
            $drivers[] = $fcm;
        } else {
            $fcmLegacy = new FcmLegacy($this->config);
            if ($fcmLegacy->isConfigured()) {
                $drivers[] = $fcmLegacy;
            }
        }

        return $drivers;
    }

    public function getWebDriver(): ?DriverInterface
    {
        $drivers = $this->getDrivers();

        // If Fcm driver is available use it
        foreach ($drivers as $driver) {
            if ($driver instanceof Fcm || $driver instanceof FcmLegacy) {
                return $driver;
            }
        }

        // Fallback to Proxy driver
        foreach ($drivers as $driver) {
            if ($driver instanceof Proxy) {
                return $driver;
            }
        }

        return null;
    }

}