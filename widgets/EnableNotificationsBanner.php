<?php

namespace humhub\modules\fcmPush\widgets;

use humhub\components\Widget;
use humhub\widgets\bootstrap\Alert;
use humhub\widgets\bootstrap\Button;
use Yii;
use yii\helpers\Html;

/**
 * One-time banner inviting the user to turn on browser push notifications.
 *
 * Rendered by Events::onLayoutAddonInit() on the first full page after login once all
 * user gates are closed (see docs/DEVELOPER.md). All variants are rendered hidden; the JS
 * side (humhub.firebase.js initEnableNotificationsBanner()) shows the banner only when the
 * Notification API exists and permission is not yet granted, and picks the variant:
 *
 * - `default` (never asked / reset): invite the user to enable notifications. The button
 *   calls enableNotificationsButtonHandler() from a click, so Notification.requestPermission()
 *   runs inside a user gesture - the only way it works on Firefox, Safari/iOS and Chrome's
 *   quiet permission UI.
 * - `denied` (blocked by the user, or "automatically blocked" by the browser after repeated
 *   dismissals): the page cannot prompt anymore and only the user can lift the block in the
 *   browser. The banner shows the steps for the detected browser, registers the token
 *   automatically as soon as the permission changes to granted (Permissions API), and
 *   offers a button to check again for browsers without that event.
 *
 * @since 2.3.1
 */
class EnableNotificationsBanner extends Widget
{
    public const ID = 'fcm-push-enable-notifications-banner';

    public function run()
    {
        $default = Html::tag(
            'div',
            Yii::t('FcmPushModule.base', 'Would you like to receive push notifications in this browser?')
            . Html::tag('div', Button::accent(Yii::t('FcmPushModule.base', 'Enable notifications'))
                ->icon('bell')
                ->action('firebase.enableNotificationsButtonHandler')
                ->loader(false)
                ->sm(), ['class' => 'mt-2']),
            ['class' => 'd-none', 'data-fcm-push-permission' => 'default'],
        );

        // One instruction per browser family; JS shows the matching one (see detectBrowser()).
        $steps = [
            'chromium' => Yii::t('FcmPushModule.base', 'Click the icon on the left of the address bar, then allow or reset "Notifications".'),
            'firefox' => Yii::t('FcmPushModule.base', 'Click the permissions icon on the left of the address bar and remove the blocked "Notifications" entry, then click the button below.'),
            'safari' => Yii::t('FcmPushModule.base', 'Open Safari > Settings > Websites > Notifications and set this website to "Allow", then click the button below.'),
            'ios' => Yii::t('FcmPushModule.base', 'Open the iOS Settings app > Notifications, select this app and allow notifications, then click the button below.'),
        ];
        $stepsHtml = '';
        foreach ($steps as $browser => $text) {
            $stepsHtml .= Html::tag('div', $text, ['class' => 'd-none mt-1', 'data-fcm-push-browser' => $browser]);
        }

        $denied = Html::tag(
            'div',
            Html::tag('strong', Yii::t('FcmPushModule.base', 'Push notifications are blocked for this site in your browser.'))
            . $stepsHtml
            . Html::tag('div', Button::light(Yii::t('FcmPushModule.base', 'I have allowed notifications'))
                ->icon('bell')
                ->action('firebase.enableNotificationsButtonHandler')
                ->loader(false)
                ->sm(), ['class' => 'mt-2']),
            ['class' => 'd-none', 'data-fcm-push-permission' => 'denied'],
        );

        $alert = Alert::info($default . $denied)
            ->id(self::ID)
            ->cssClass('d-none position-fixed bottom-0 end-0 m-3 shadow')
            ->style('max-width: 420px; z-index: 1040');

        $id = self::ID;
        $this->view->registerJs("humhub.modules.firebase.initEnableNotificationsBanner('#{$id}');");

        return $alert;
    }
}
