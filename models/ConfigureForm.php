<?php

namespace humhub\modules\fcmPush\models;

use humhub\modules\fcmPush\Module;
use Yii;
use yii\base\Model;
use yii\helpers\Json;

class ConfigureForm extends Model
{

    public $senderId;

    public $json;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['senderId', 'json'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'senderId' => Yii::t('FcmPushModule.base', 'Sender ID'),
            'json' => Yii::t('FcmPushModule.base', 'JSON'),
        ];
    }

    public function loadSettings()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        $settings = $module->settings;

        $this->senderId = $settings->get('senderId');
        $this->json = $settings->get('json');

        return true;
    }

    public function saveSettings()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        $module->settings->set('senderId', $this->senderId);
        $module->settings->set('json', $this->json);

        return true;
    }

    public function getJsonAsArray()
    {
        return Json::decode($this->json);
    }

    public function isActive()
    {
        if (empty($this->senderId) || empty($this->json)) {
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
