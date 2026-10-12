<?php

namespace humhub\modules\fcmPush\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\components\Response;
use humhub\modules\fcmPush\Module;
use humhub\modules\fcmPush\services\DriverService;
use humhub\modules\fcmPush\services\TokenService;
use Yii;
use yii\web\HttpException;

/**
 * @property Module $module
 */
class TokenController extends Controller
{
    public $enableCsrfValidation = false;

    public $access = ControllerAccess::class;

    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $this->forcePostRequest();
        return parent::beforeAction($action);
    }

    public function actionUpdate()
    {
        return $this->update(false);
    }

    public function actionUpdateMobileApp()
    {
        return $this->update(true);
    }

    public function actionDelete()
    {
        return $this->delete(false);
    }

    public function actionDeleteMobileApp()
    {
        return $this->delete(true);
    }

    /**
     * @return string|null the posted token, or null if it is missing, empty or not a plain string
     */
    private function getTokenParam(): ?string
    {
        $token = Yii::$app->request->post('token');

        return (is_string($token) && $token !== '') ? $token : null;
    }

    private function update(bool $mobile)
    {
        if (Yii::$app->user->isGuest) {
            throw new HttpException(401, 'Login required!');
        }

        $token = $this->getTokenParam();
        if ($token === null) {
            Yii::$app->response->statusCode = 400;
            return $this->asJson(['success' => false, 'message' => 'No token given!']);
        }

        $driverService = new DriverService($this->module->getConfigureForm());
        $tokenService = new TokenService();

        $driver = $mobile ? $driverService->getMobileAppDriver() : $driverService->getWebDriver();
        if (!$driver) {
            Yii::error('Could not update token for ' . ($mobile ? 'mobile' : 'web') . ' app. No driver available.', 'fcm-push');

            Yii::$app->response->statusCode = 400;
            return $this->asJson(['success' => false, 'message' => 'No push driver available!']);
        }

        return $this->asJson([
            'success' => $tokenService->storeTokenForUser(
                Yii::$app->user->getIdentity(),
                $driver,
                $token,
            ),
        ]);
    }

    private function delete(bool $mobile)
    {
        $token = $this->getTokenParam();
        if ($token === null) {
            Yii::$app->response->statusCode = 400;
            return $this->asJson(['success' => false, 'message' => 'No token given!']);
        }

        $driverService = new DriverService($this->module->getConfigureForm());
        $tokenService = new TokenService();

        $driver = $mobile ? $driverService->getMobileAppDriver() : $driverService->getWebDriver();
        if (!$driver) {
            Yii::error('Could not delete token for ' . ($mobile ? 'mobile' : 'web') . ' app. No driver available.', 'fcm-push');

            Yii::$app->response->statusCode = 400;
            return $this->asJson(['success' => false, 'message' => 'No push driver available!']);
        }

        return $this->asJson([
            'success' => $tokenService->deleteToken($token),
        ]);
    }
}
