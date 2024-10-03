<?php

namespace humhub\modules\fcmPush\controllers;

use humhub\components\Controller;
use humhub\modules\fcmPush\Module;
use Yii;
use yii\web\Response;

/**
 * @property Module $module
 */
class StatusController extends Controller
{
    public function actionIndex()
    {
        if (!$this->module->isActivated) {
            return $this->returnStatus(404, 'FCM Module is not installed');
        }

        if (!$this->module->getDriverService()->hasConfiguredDriver()) {
            return $this->returnStatus(501, 'Push Proxy is not configured');
        }

        return $this->returnStatus(200, 'OK, Push (Proxy) is correctly configured');
    }

    private function returnStatus(int $code, string $message): Response
    {
        Yii::$app->response->statusCode = $code;

        return $this->asJson([
            'code' => $code,
            'message' => $message,
        ]);
    }
}
