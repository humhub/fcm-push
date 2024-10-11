<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\fcmPush\components;

use humhub\modules\fcmPush\services\WellKnownService;
use yii\base\Component;
use yii\web\UrlRuleInterface;

class UrlRule extends Component implements UrlRuleInterface
{
    /**
     * @inheritdoc
     */
    public function createUrl($manager, $route, $params)
    {
        if ($route === trim(WellKnownService::URL_ROUTE, '/') && isset($params['file'])) {
            return WellKnownService::URL_PREFIX . $params['file'];
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function parseRequest($manager, $request)
    {
        $path = $request->getPathInfo();
        if (str_starts_with($path, WellKnownService::URL_PREFIX)) {
            return WellKnownService::instance($path)->getRuleRoute() ?? false;
        }

        return false;
    }
}
