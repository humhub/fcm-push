<?php

namespace humhub\modules\fcmPush\widgets;

use humhub\components\Widget;
use humhub\widgets\bootstrap\Alert;
use humhub\widgets\bootstrap\Button;
use Yii;
use yii\helpers\Html;

/**
 * Banner inviting the user to turn on browser push notifications.
 *
 * Rendered by Events::onLayoutAddonInit() on the first full page after login once all
 * user gates are closed (see docs/DEVELOPER.md). It is rendered hidden; the JS side
 * (humhub.firebase.js initEnableNotificationsBanner()) only shows it when the Notification
 * API exists, the permission has not been decided yet (`default`) and the user has not
 * clicked "No thanks" within the last 30 days (stored in localStorage).
 *
 * When notifications are blocked (`denied`) nothing is shown: the user blocked them on
 * purpose, and the steps to lift the block are on the notification settings page
 * (RegisterDeviceTokenButton) instead.
 *
 * The "Enable notifications" button calls enableNotificationsButtonHandler() from a click,
 * so Notification.requestPermission() runs inside a user gesture - the only way it works on
 * Firefox, Safari/iOS and Chrome's quiet permission UI.
 *
 * @since 2.3.1
 */
class EnableNotificationsBanner extends Widget
{
    public const ID = 'fcm-push-enable-notifications-banner';

    public function run()
    {
        $buttons = Button::accent(Yii::t('FcmPushModule.base', 'Enable notifications'))
                ->icon('bell')
                ->action('firebase.enableNotificationsButtonHandler')
                ->loader(false)
                ->sm()
            . ' '
            . Button::light(Yii::t('FcmPushModule.base', 'No thanks'))
                ->action('firebase.dismissEnableNotificationsBanner')
                ->loader(false)
                ->sm();

        $alert = Alert::info(
            Yii::t('FcmPushModule.base', 'Would you like to receive push notifications in this browser?')
            . Html::tag('div', $buttons, ['class' => 'mt-2']),
        )
            ->id(self::ID)
            ->closeButton(false)
            ->cssClass('d-none position-fixed bottom-0 end-0 m-3 shadow')
            ->style('max-width: 420px; z-index: 1040');

        $id = self::ID;
        $this->view->registerJs("humhub.modules.firebase.initEnableNotificationsBanner('#{$id}');");

        return $alert;
    }
}
