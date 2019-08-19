<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\fcmPush;


use humhub\modules\web\pwa\controllers\ManifestController;

class Events
{

    public static function onManifestControllerInit($event)
    {
        /** @var ManifestController $controller */
        $controller = $event->sender;
        $controller->manifest['gcm_sender_id'] = 103953800507;

    }

}