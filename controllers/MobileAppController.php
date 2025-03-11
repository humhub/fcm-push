<?php

namespace humhub\modules\fcmPush\controllers;

use humhub\components\Controller;
use humhub\modules\fcmPush\helpers\MobileAppHelper;
use Yii;
use yii\helpers\Url;

class MobileAppController extends Controller
{
    public function actionInstanceOpener()
    {
        MobileAppHelper::registerShowOpenerScript();
        Yii::$app->view->registerJs('window.location.href = "' . Url::home() . '";');
        return $this->renderContent('');
    }
}
