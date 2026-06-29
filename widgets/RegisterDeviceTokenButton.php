<?php

namespace humhub\modules\fcmPush\widgets;

use humhub\components\Widget;
use humhub\helpers\DeviceDetectorHelper;
use humhub\modules\fcmPush\Module;
use humhub\modules\fcmPush\services\DriverService;
use humhub\modules\fcmPush\services\TokenService;
use humhub\widgets\bootstrap\Button;
use Yii;

class RegisterDeviceTokenButton extends Widget
{
    public function run()
    {
        if (
            Yii::$app->user->isGuest
            || (!DeviceDetectorHelper::isIos() && !DeviceDetectorHelper::isIosApp())
        ) {
            // Only show the button for logged-in users on iOS devices, web or app (see https://github.com/humhub/humhub-internal/issues/1243)
            return '';
        }

        /* @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        $driverService = new DriverService($module->getConfigureForm());
        $driver = DeviceDetectorHelper::isIosApp() ? $driverService->getMobileAppDriver() : $driverService->getWebDriver();
        if (!$driver) {
            return '';
        }

        $tokenService = new TokenService();
        if ($tokenService->getTokensForUser(Yii::$app->user->identity, $driver)) {
            // Token is already registered for this user
            return '';
        }

        return Button::accent(Yii::t('FcmPushModule.base', 'Enable Mobile notifications'))
            ->icon('bell')
            ->action('firebase.enableNotificationsButtonHandler')
            ->loader(false)
            ->cssClass('mb-4');
    }
}
