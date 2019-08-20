<?php

namespace humhub\modules\fcmPush\models;

use humhub\modules\fcmPush\Module;
use Yii;
use yii\base\Model;

class ConfigureForm extends Model
{

    public $senderId;

    public $serverKey;

    public $projectId;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['senderId', 'serverKey', 'projectId'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'senderId' => Yii::t('FcmPushModule.base', 'Sender ID'),
            'serverKey' => Yii::t('FcmPushModule.base', 'Server Key'),
            'projectId' => Yii::t('FcmPushModule.base', 'Project ID'),
        ];
    }

    public function loadSettings()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        $settings = $module->settings;

        $this->senderId = $settings->get('senderId');
        $this->serverKey = $settings->get('serverKey');
        $this->projectId = $settings->get('projectId');

        return true;
    }

    public function saveSettings()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        $module->settings->set('senderId', $this->senderId);
        $module->settings->set('serverKey', $this->serverKey);
        $module->settings->set('projectId', $this->projectId);

        return true;
    }

    public function isActive()
    {
        if (empty($this->senderId) || empty($this->serverKey) || empty($this->projectId)) {
            return false;
        }
        
        return true;
    }


    public static function getInstance()
    {
        $config = new static;
        $config->loadSettings();

        return $config;
    }

}
