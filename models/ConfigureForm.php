<?php

namespace humhub\modules\fcmPush\models;

use Exception;
use humhub\modules\fcmPush\Module;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\helpers\FileHelper;
use yii\helpers\Json;

class ConfigureForm extends Model
{
    public const FILE_ASSET_LINKS = 'assetlinks.json';
    public const FILE_APPLE_ASSOCIATION = 'apple-app-site-association';

    public $enableEmailGoService;

    public $humhubInstallId;

    public $senderId;

    public $json;

    public $serverKey;

    public $humhubApiKey;

    public $disableAuthChoicesIos;

    public $fileAssetlinksJson;

    public $fileAppleAppSiteAssociation;

    /**
     * Validate JSON field params
     *
     * @param $arrayPattern
     * @param $arrayCheck
     * @return string[]
     */
    private function validateJsonParams($arrayPattern, $arrayCheck)
    {
        $errors = ["contains_no" => "", "empty" => "", "invalid" => ""];

        foreach ($arrayPattern as $key => $value) {
            if (isset($arrayCheck[$key])) {
                if (empty($arrayCheck[$key])) {
                    $errors["empty"] .= $errors["empty"] == "" ? "\"$key\"" : ", \"$key\"";
                } else {
                    $condition = false;
                    switch ($value['type']) {
                        case "string":
                            if (isset($value['value'])) {
                                $condition = $value['value'] !== $arrayCheck[$key];
                            } elseif (isset($value['pattern'])) {
                                if ($value['pattern'] == "alfa-numeric" || $value['pattern'] == "numeric") {
                                    $condition = $value['pattern'] == "numeric"
                                        ? !preg_match("/^\\d+$/", $arrayCheck[$key])
                                        : !ctype_alnum($arrayCheck[$key]);
                                }
                            }
                            break;
                        case "email":
                            $condition = !filter_var($arrayCheck[$key], FILTER_VALIDATE_EMAIL);
                            break;
                        case "url":
                            $url_validation_regex = "/^https?:\\/\\/(?:www\\.)?[-a-zA-Z0-9@:%._\\+~#=]{1,256}\\.[a-zA-Z0-9()]{1,6}\\b(?:[-a-zA-Z0-9()@:%_\\+.~#?&\\/=]*)$/";
                            $condition = !preg_match($url_validation_regex, $arrayCheck[$key]);
                            break;
                        default:
                    }

                    if ($condition) {
                        $errors["invalid"] .= $errors["invalid"] == "" ? "\"$key\"" : ", \"$key\"";
                    }
                }
            } else {
                $errors["contains_no"] .= $errors["contains_no"] == "" ? "\"$key\"" : ", \"$key\"";
            }
        }

        return $errors;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['enableEmailGoService', 'disableAuthChoicesIos'], 'boolean'],
            [['senderId'], 'number'],
            [['serverKey', 'json', 'humhubApiKey'], 'safe'],
            [['fileAssetlinksJson', 'fileAppleAppSiteAssociation'], 'string'],
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

                $googleServiceParamsPattern = [
                    "type" => ["type" => "string", "value" => "service_account"],
                    "project_id" => ["type" => "string"],
                    "private_key_id" => ["type" => "string", "pattern" => "alfa-numeric"],
                    "private_key" => ["type" => "string"],
                    "client_email" => ["type" => "email"],
                    "client_id" => ["type" => "string", "pattern" => "numeric"],
                    "auth_uri" => ["type" => "url"],
                    "token_uri" => ["type" => "url"],
                    "auth_provider_x509_cert_url" => ["type" => "url"],
                    "client_x509_cert_url" => ["type" => "url"],
                ];
                $result = $this->validateJsonParams($googleServiceParamsPattern, $data);

                if ($result["contains_no"] !== "") {
                    $this->addError($attribute, "JSON contains no {$result['contains_no']}.");
                    return;
                }
                if ($result["empty"] !== "") {
                    $this->addError($attribute, "JSON has empty {$result['empty']}.");
                    return;
                }
                if ($result["invalid"] !== "") {
                    $this->addError($attribute, "JSON has invalid value in {$result['invalid']}.");
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
            'disableAuthChoicesIos' => Yii::t('FcmPushModule.base', 'Disable AuthChoices on iOS App'),
            'fileAssetlinksJson' => Yii::t('FcmPushModule.base', 'Well-known file {fileName}', ['fileName' => '"' . self::FILE_ASSET_LINKS . '"']),
            'fileAppleAppSiteAssociation' => Yii::t('FcmPushModule.base', 'Well-known file {fileName}', ['fileName' => '"' . self::FILE_APPLE_ASSOCIATION . '"']),
        ];
    }

    public function attributeHints()
    {
        return [
            'humhubInstallId' => Yii::t('FcmPushModule.base', 'Use this ID to register your API Key.'),
            'serverKey' => Yii::t('FcmPushModule.base', 'Please switch to the new "Firebase Cloud Messaging API (V1)" and enter a JSON file in the field above. The old legacy API is only temporarily available for existing installations and is no longer supported or maintained.'),
            'json' => Yii::t('FcmPushModule.base', 'Paste the content of the service account JSON files here. You can find more information in the module instructions.'),
        ];
    }

    public function loadSettings()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        $settings = $module->settings;

        /** @var \humhub\modules\admin\Module $adminModule */
        $adminModule = Yii::$app->getModule('admin');

        $this->enableEmailGoService = $settings->get('enableEmailGoService', false);
        $this->humhubInstallId = $adminModule->settings->get('installationId');
        $this->senderId = $settings->get('senderId');
        $this->json = $settings->get('json');
        $this->serverKey = $settings->get('serverKey');
        $this->humhubApiKey = $settings->get('humhubApiKey');
        $this->disableAuthChoicesIos = $settings->get('disableAuthChoicesIos');
        $this->fileAssetlinksJson = $this->getFileContent(self::FILE_ASSET_LINKS);
        $this->fileAppleAppSiteAssociation = $this->getFileContent(self::FILE_APPLE_ASSOCIATION);

        return true;
    }

    public function saveSettings()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('fcm-push');

        $module->settings->set('enableEmailGoService', $this->enableEmailGoService);
        $module->settings->set('senderId', $this->senderId);
        $module->settings->set('json', $this->json);
        $module->settings->set('serverKey', $this->serverKey);
        $module->settings->set('humhubApiKey', $this->humhubApiKey);
        $module->settings->set('disableAuthChoicesIos', $this->disableAuthChoicesIos);
        $this->saveFile(self::FILE_ASSET_LINKS, $this->fileAssetlinksJson);
        $this->saveFile(self::FILE_APPLE_ASSOCIATION, $this->fileAppleAppSiteAssociation);

        return true;
    }

    public function getJsonAsArray()
    {
        return Json::decode($this->json);
    }

    public static function getInstance()
    {
        $config = new static();
        $config->loadSettings();

        return $config;
    }

    protected function getFilePath(string $fileName): string
    {
        return Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . '.well-known' . DIRECTORY_SEPARATOR . $fileName;
    }

    protected function getFileContent(string $fileName): string
    {
        $filePath = $this->getFilePath($fileName);
        return is_file($filePath) ? file_get_contents($filePath) : '';
    }

    protected function saveFile(string $fileName, ?string $content): bool
    {
        $filePath = $this->getFilePath($fileName);
        if (!file_exists($filePath)) {
            if (empty($content) && $content !== '0') {
                // Don't create a file with empty content
                return true;
            }

            $dirPath = dirname($filePath);
            try {
                FileHelper::createDirectory($dirPath);
            } catch (Exception $ex) {
                Yii::error('Cannot create the dir ' . $dirPath . ' Error: ' . $ex->getMessage(), 'fcm-push');
            }
        }

        try {
            return (bool) file_put_contents($filePath, $content);
        } catch (Exception $ex) {
            Yii::error('Cannot update the file ' . $filePath . ' Error: ' . $ex->getMessage(), 'fcm-push');
        }

        return false;
    }
}
