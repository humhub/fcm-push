<?php

namespace humhub\modules\fcmPush\helpers;

use humhub\helpers\DeviceDetectorHelper;
use Yii;

class WebAppHelper
{
    public const SESSION_VAR_UNREGISTER_NOTIFICATION = 'webAppUnregisterNotification';
    public const SESSION_VAR_SHOW_ENABLE_NOTIFICATIONS_BANNER = 'webAppShowEnableNotificationsBanner';

    /**
     * Whether any user gate (legal confirmation, 2FA check, forced password change, ...)
     * is currently open for the logged-in user. While a gate is open the user is inside a
     * mandatory flow, so nothing optional like the enable-notifications banner should be shown.
     * GateManager::findOpenGate() cannot be used here: it returns null on the gate's own
     * page, which is exactly where the banner must not appear.
     */
    public static function hasOpenGate(): bool
    {
        foreach (Yii::$app->gateManager->getGates() as $gate) {
            if ($gate->isOpen()) {
                return true;
            }
        }

        return false;
    }

    public static function unregisterNotificationScript()
    {
        if (DeviceDetectorHelper::isAppRequest()) {
            return;
        }

        Yii::$app->view->registerJs('humhub.modules.firebase.unregisterNotification();');
    }
}
