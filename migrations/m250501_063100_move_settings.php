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
        $moduleSettings = $module->settings;

        // Move settings from this module to core
        $settingsMap = [
            // 'old setting name from module' => 'new setting name in core'
            'enableEmailGoService' => 'mailerLinkService',
            'fileAssetLinks' => 'fileAssetLinks',
            'fileAppleAssociation' => 'fileAppleAssociation',
        ];

        foreach ($settingsMap as $moduleSettingName => $coreSettingName) {
            $moduleSettingValue = $moduleSettings->get($moduleSettingName);
            if ($moduleSettingValue !== null) {
                Yii::$app->settings->set($coreSettingName, $moduleSettingValue);
                $moduleSettings->delete($moduleSettingName);
            }
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
