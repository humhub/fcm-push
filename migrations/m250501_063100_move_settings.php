<?php

use humhub\components\Migration;
use humhub\modules\fcmPush\Module;

class m250501_063100_move_settings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $module = Yii::$app->getModule('fcm-push');
        if (!$module instanceof Module) {
            return;
        }

        $moduleSettingValue = $module->settings->get('enableEmailGoService');
        if ($moduleSettingValue !== null) {
            Yii::$app->settings->set('mailerLinkService', $moduleSettingValue);
            $module->settings->delete('enableEmailGoService');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250501_063100_move_settings cannot be reverted.\n";

        return false;
    }
}
