<?php

namespace humhub\modules\fcmPush\controllers;

use humhub\components\Controller;
use humhub\modules\fcmPush\helpers\MobileAppHelper;
use Yii;

class MobileAppController extends Controller
{
    public function actionInstanceOpener()
    {
        // Send to the mobile app to display the instance opener
        Yii::$app->session->set(MobileAppHelper::SESSION_VAR_SHOW_OPENER, 1);

        // Stay on the same page, because when we come back from the mobile app to this instance
        return $this->htmlRedirect(Yii::$app->request->referrer);
    }
}
