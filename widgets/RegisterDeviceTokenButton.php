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
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Browser push notification state on the user's notification settings page.
 *
 * All parts are rendered hidden; humhub.firebase.js initNotificationSettings() shows the one
 * matching this browser: the "Enable notifications" button, the steps to allow notifications
 * again when they are blocked for the site, or (iOS outside of an installed PWA) the hint to
 * add the site to the Home Screen first.
 */
class RegisterDeviceTokenButton extends Widget
{
    public const ID = 'fcm-push-notification-settings';

    public function run()
    {
        // Native apps register their token through the Flutter bridge (MobileAppHelper),
        // not the browser Notification API.
        if (Yii::$app->user->isGuest || DeviceDetectorHelper::isAppRequest()) {
            return '';
        }

        /* @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        $driver = (new DriverService($module->getConfigureForm()))->getWebDriver();
        if (!$driver) {
            return '';
        }

        $enable = Html::tag('div', Button::accent(Yii::t('FcmPushModule.base', 'Enable notifications'))
            ->icon('bell')
            ->action('firebase.enableNotificationsButtonHandler')
            ->loader(false)
            ->id('fcm-push-enable-notifications'), ['class' => 'd-none mb-4', 'data-fcm-push-state' => 'enable']);

        // One instruction per browser family; JS shows the matching one (see detectBrowser()).
        $steps = [
            'chromium' => Yii::t('FcmPushModule.base', 'Click the icon on the left of the address bar, allow "Notifications" and reload the page.'),
            'firefox' => Yii::t('FcmPushModule.base', 'Click the permissions icon on the left of the address bar, remove the blocked "Notifications" entry and reload the page.'),
            'safari' => Yii::t('FcmPushModule.base', 'Open Safari > Settings > Websites > Notifications, set this website to "Allow" and reload the page.'),
            'ios' => Yii::t('FcmPushModule.base', 'Open the iOS Settings app > Notifications, select this app, allow notifications and reload the page.'),
        ];
        $stepsHtml = '';
        foreach ($steps as $browser => $text) {
            $stepsHtml .= Html::tag('div', $text, ['class' => 'd-none', 'data-fcm-push-browser' => $browser]);
        }
        $denied = Html::tag('div', Alert::warning(
            Html::tag('strong', Yii::t('FcmPushModule.base', 'Push notifications are blocked for this site in your browser.'))
            . $stepsHtml,
        )
            ->icon('bell-slash')
            ->closeButton(false), ['class' => 'd-none', 'data-fcm-push-state' => 'denied']);

        // On iOS, the Notification API is only exposed when the site runs as an installed
        // PWA (added to the Home Screen), see https://github.com/humhub/humhub-internal/issues/1243
        $install = '';
        if (DeviceDetectorHelper::isIos()) {
            $install = Html::tag('div', Alert::warning(Yii::t('FcmPushModule.base', 'Add this site to your Home Screen to turn on notifications: tap the "Share" icon -> "Add to Home Screen"'))
                ->icon('mobile')
                ->id('fcm-push-add-to-home-screen')
                ->closeButton(false), ['class' => 'd-none', 'data-fcm-push-state' => 'install']);
        }

        $this->view->registerJs('humhub.modules.firebase.initNotificationSettings(' . Json::htmlEncode('#' . self::ID) . ');');

        return Html::tag('div', $enable . $denied . $install, [
            'id' => self::ID,
            'data-fcm-push-settings' => true,
            'data-fcm-push-ios' => DeviceDetectorHelper::isIos() ?: null,
            // Whether this device is registered is checked per device: the user's server-side
            // tokens may all belong to other devices, so only this device's localStorage token
            // can tell, and it must still exist server-side (it may have been deleted there,
            // e.g. after FCM rejected it).
            'data-fcm-push-server-tokens' => Json::encode((new TokenService())->getTokensForUser(Yii::$app->user->identity, $driver)),
        ]);
    }
}
