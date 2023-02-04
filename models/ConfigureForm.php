<?php

namespace humhub\modules\fcmPush\models;

use humhub\modules\fcmPush\Module;
use Yii;
use yii\base\InvalidArgumentException;
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
            ['json', function ($attribute, $params, $validator) {
                try {
                    $data = Json::decode($this->$attribute);
                } catch (InvalidArgumentException $ex) {
                    $this->addError($attribute, 'Invalid JSON input.');
                    return;
                }
                if (empty($data)) {
                    $this->addError($attribute, 'Empty JSON input.');
                    return;
                }
                if (!isset($data['project_id'])) {
                    $this->addError($attribute, 'JSON contain no project id.');
                    return;
                }
            }],

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
