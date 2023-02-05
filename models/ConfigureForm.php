<?php

namespace humhub\modules\fcmPush\models;

use humhub\modules\fcmPush\Module;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\helpers\Json;

class ConfigureForm extends Model
{
    public $humhubInstallId;

    public $senderId;

    public $json;

    public $serverKey;

    public $humhubApiKey;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['senderId'], 'number'],
            [['serverKey', 'json', 'humhubApiKey'], 'safe'],
            ['json', function ($attribute, $params, $validator) {
                if (empty($this->$attribute)) {
                    return;
                }
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
            'humhubInstallId' => Yii::t('FcmPushModule.base', 'Install ID'),
            'humhubApiKey' => Yii::t('FcmPushModule.base', 'API Key'),
            'senderId' => Yii::t('FcmPushModule.base', 'Sender ID'),
            'json' => Yii::t('FcmPushModule.base', 'Service Account (JSON file)'),
            'serverKey' => Yii::t('FcmPushModule.base', 'Cloud Messaging API (Legacy)'),
        ];
    }

    public function attributeHints()
    {
        return [
            'humhubInstallId' => 'Use this ID to register your API Key.',
            'serverKey' => 'Please switch to the new "Firebase Cloud Messaging API (V1)" and enter a JSON file in the field above. The old legacy API is only temporarily available for existing installations and is no longer supported or maintained. ',
            'json' => 'Paste the content of the service account JSON files here. You can find more information in the module instructions.'
        ];
    }

    public function loadSettings()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        $settings = $module->settings;

        /** @var \humhub\modules\admin\Module $adminModule */
        $adminModule = Yii::$app->getModule('admin');

        $this->humhubInstallId = $adminModule->settings->get('installationId');
        $this->senderId = $settings->get('senderId');
        $this->json = $settings->get('json');
        $this->serverKey = $settings->get('serverKey');
        $this->humhubApiKey = $settings->get('humhubApiKey');

        return true;
    }

    public function saveSettings()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        $module->settings->set('senderId', $this->senderId);
        $module->settings->set('json', $this->json);
        $module->settings->set('serverKey', $this->serverKey);
        $module->settings->set('humhubApiKey', $this->humhubApiKey);

        return true;
    }

    public function getJsonAsArray()
    {
        return Json::decode($this->json);
    }

    public static function getInstance()
    {
        $config = new static;
        $config->loadSettings();

        return $config;
    }

}