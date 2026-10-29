<?php

namespace humhub\modules\fcmPush\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\fcmPush\Module;
use humhub\modules\fcmPush\services\MessagingService;
use Yii;

/**
 * Endpoints used by the web view of the HumHub mobile app.
 *
 * @property Module $module
 * @since 2.3.3
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
     */
    public function actionNotificationCount()
    {
        $count = (new MessagingService($this->module->getConfigureForm()))
            ->getNotificationCount(Yii::$app->user->getIdentity());

        return $this->asJson(['count' => $count]);
    }
}
