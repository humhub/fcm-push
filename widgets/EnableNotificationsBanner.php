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
 * user gates are closed (see docs/DEVELOPER.md). The banner is only made visible by JS
 * when the Notification API exists and permission is not yet granted:
 *
 * - `default` (never asked / reset): invite the user to enable notifications. The button
 *   calls enableNotificationsButtonHandler() from a click, so Notification.requestPermission()
 *   runs inside a user gesture - the only way it works on Firefox, Safari/iOS and Chrome's
 *   quiet permission UI.
 * - `denied` (blocked by the user, or "automatically blocked" by the browser after repeated
 *   dismissals): the page cannot prompt anymore, so explain how to allow notifications in
 *   the browser's site settings first. The button stays: once the user has allowed the site
 *   in the browser, clicking it registers the token right away.
 *
 * @since 2.3.1
 */
class EnableNotificationsBanner extends Widget
{
    public const ID = 'fcm-push-enable-notifications-banner';

    public function run()
    {
        $texts = Html::tag(
            'span',
            Yii::t('FcmPushModule.base', 'Would you like to receive push notifications in this browser?'),
            ['class' => 'd-none', 'data-fcm-push-permission' => 'default'],
        ) . Html::tag(
            'span',
            Yii::t('FcmPushModule.base', 'Push notifications are blocked for this site in your browser. Allow them in the site settings of your browser (icon next to the address bar), then click "Enable notifications".'),
            ['class' => 'd-none', 'data-fcm-push-permission' => 'denied'],
        );

        $button = Button::accent(Yii::t('FcmPushModule.base', 'Enable notifications'))
            ->icon('bell')
            ->action('firebase.enableNotificationsButtonHandler')
            ->loader(false)
            ->sm();

        $alert = Alert::info($texts . Html::tag('div', $button, ['class' => 'mt-2']))
            ->id(self::ID)
            ->cssClass('d-none position-fixed bottom-0 end-0 m-3 shadow')
            ->style('max-width: 420px; z-index: 1040');

        $id = self::ID;
        $this->view->registerJs(<<<JS
            if ('Notification' in window && Notification.permission !== 'granted') {
                const \$banner = \$('#{$id}');
                const state = Notification.permission === 'denied' ? 'denied' : 'default';
                \$banner.find('[data-fcm-push-permission="' + state + '"]').removeClass('d-none');
                \$banner.removeClass('d-none');
            }
        JS);

        return $alert;
    }
}
