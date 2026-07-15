<?php

namespace humhub\modules\fcmPush\widgets;

use humhub\components\Widget;
use humhub\helpers\DeviceDetectorHelper;
use humhub\modules\fcmPush\Module;
use humhub\modules\fcmPush\services\DriverService;
use humhub\modules\fcmPush\services\TokenService;
use humhub\widgets\bootstrap\Alert;
use humhub\widgets\bootstrap\Button;
use Yii;
use yii\helpers\Json;

class RegisterDeviceTokenButton extends Widget
{
    public function run()
    {
        if (
            Yii::$app->user->isGuest
            || !DeviceDetectorHelper::isIos()
            || DeviceDetectorHelper::isIosApp()
        ) {
            // Only show the button for logged-in users on iOS devices browser or PWA (see https://github.com/humhub/humhub-internal/issues/1243)
            return '';
        }

        /* @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        $driverService = new DriverService($module->getConfigureForm());
        $driver = DeviceDetectorHelper::isIosApp() ? $driverService->getMobileAppDriver() : $driverService->getWebDriver();
        if (!$driver) {
            return '';
        }

        $buttonId = 'fcm-push-enable-notifications';
        $alertId = 'fcm-push-add-to-home-screen';

        $button = Button::accent(Yii::t('FcmPushModule.base', 'Enable Mobile notifications'))
            ->icon('bell')
            ->action('firebase.enableNotificationsButtonHandler')
            ->loader(false)
            ->id($buttonId)
            ->cssClass('d-none mb-4');

        $alert = Alert::warning(Yii::t('FcmPushModule.base', 'Add this site to your Home Screen to turn on notifications: tap the "Share" icon -> "Add to Home Screen"'))
            ->icon('mobile')
            ->id($alertId)
            ->closeButton(false)
            ->cssClass('d-none');

        $serverTokens = Json::htmlEncode(
            (new TokenService())->getTokensForUser(Yii::$app->user->identity, $driver),
        );

        // On iOS, the Notification API is only exposed when the site runs as an installed
        // PWA (added to the Home Screen). If it is available, offer the enable button;
        // otherwise show the hint alert telling the user to install the app first.
        // Whether a token is already registered must be checked per device, not per
        // user: the user's server-side tokens may all belong to other devices, so
        // only this device's localStorage token can tell if *this* device is
        // registered — and it must still exist server-side (it may have been
        // deleted there, e.g. after FCM rejected it), hence the comparison against
        // the user's registered tokens instead of trusting the local cache alone.
        $this->view->registerJs(<<<JS
            if ('Notification' in window) {
                const localToken = humhub.modules.firebase.getTokenLocalStore();
                if (!localToken || !{$serverTokens}.includes(localToken)) {
                    \$('#{$buttonId}').removeClass('d-none');
                }
            } else {
                \$('#{$alertId}').removeClass('d-none');
            }
        JS);

        return $button . $alert;
    }
}
