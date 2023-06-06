<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "union_openid".
 *
 * @property int $id
 * @property string $official_openid 公众号openid
 * @property string $secret_key 公众号加密密钥
 * @property string $wx_openid 小程序openid
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 */
class UnionOpenid extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'union_openid';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['updated_at', 'created_at'], 'integer'],
            [['official_openid', 'secret_key', 'wx_openid'], 'string', 'max' => 50],
            [['official_openid'], 'unique'],
            [['secret_key'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'official_openid' => 'Official Openid',
            'secret_key' => 'Secret Key',
            'wx_openid' => 'Wx Openid',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
