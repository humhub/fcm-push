<?php

namespace humhub\modules\fcmPush\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\fcmPush\helpers\MobileAppHelper;
use humhub\modules\fcmPush\Module;

/**
 * Endpoints used by the web view of the HumHub mobile app.
 *
 * @property Module $module
 * @since 2.3.5
 */
class MobileAppController extends Controller
{
    /**
     * @inheritdoc
     */
    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
        ];
    }

    /**
     * Returns the unread count, which the mobile app shows as app icon badge (see MobileAppAsset).
     * The count is `null` if it is not sent to this request or could not be calculated, so the
     * page does not post it to the app.
     */
    public function actionNotificationCount()
    {
        return $this->asJson(['count' => MobileAppHelper::getBadgeCount()]);
    }
}
