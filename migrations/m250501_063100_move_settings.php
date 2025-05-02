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
            // 'old setting name' => ['new setting name', 'core module id']
            'enableEmailGoService' => ['mailerLinkService', 'base'],
            'disableAuthChoicesIos' => ['auth.disableChoicesIos', 'user'],
            'fileAssetLinks' => ['fileAssetLinks', 'base'],
            'fileAppleAssociation' => ['fileAppleAssociation', 'base'],
        ];

        foreach ($settingsMap as $moduleSettingName => $setting) {
            $moduleSettingValue = $moduleSettings->get($moduleSettingName);
            if ($moduleSettingValue !== null) {
                $settingOwner = $setting[1] === 'base'
                    ? Yii::$app
                    : Yii::$app->getModule($setting[1]);
                $settingOwner->settings->set($setting[0], $moduleSettingValue);
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
