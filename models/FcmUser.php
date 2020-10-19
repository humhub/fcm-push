<?php

namespace humhub\modules\fcmPush\models;

use humhub\modules\user\models\User;
use yii\db\ActiveRecord;

/**
 * Class FcmUser
 *
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $user
 */
class FcmUser extends ActiveRecord
{

    /**
     * @inheritDoc
     */
    public static function tableName()
    {
        return 'fcmpush_user';
    }

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            [['token'], 'required'],
            [['token'], 'unique'],
        ];
    }

    /**
     * @inheritDoc
     */
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = date('Y-m-d G:i:s');
        } else {
            $this->updated_at = date('Y-m-d G:i:s');
        }

        return parent::beforeSave($insert);
    }

    /**
     * Returns User relation
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

}