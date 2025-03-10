<?php

namespace humhub\modules\fcmPush\controllers;

use humhub\components\Controller;
use humhub\modules\fcmPush\helpers\MobileAppHelper;
use yii\helpers\Url;

class MobileAppController extends Controller
{
    public function actionInstanceOpener()
    {
        MobileAppHelper::registerShowOpenerScript();

        // Stay on the same page, because when we come back from the mobile app to this instance
        return $this->redirect(Url::home());
    }
}
