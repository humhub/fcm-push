<?php

namespace humhub\modules\fcmPush\controllers;

use humhub\components\Controller;
use humhub\libs\Html;
use humhub\modules\fcmPush\helpers\MobileAppHelper;
use Yii;

class MobileAppController extends Controller
{
    public function actionInstanceOpener()
    {
        // Send to the mobile app to display the instance opener
        Yii::$app->session->set(MobileAppHelper::SESSION_VAR_SHOW_OPENER, 1);

        // Stay on the same page, because when we come back from the mobile app to this instance
        // Force full page refresh to trigger the onLayoutAddonInit event
        return $this->renderContent(sprintf(
            '<script ' . Html::nonce() . '>window.location.href = "%s";</script>',
            Yii::$app->request->referrer,
        ));
    }
}
