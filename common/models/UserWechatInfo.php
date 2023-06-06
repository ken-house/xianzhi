<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "user_wechat_info".
 *
 * @property int $id
 * @property string $openid 微信标识
 * @property string $unionid 微信用户统一标识
 * @property int $gender 性别  0未知  1男  2女
 * @property string $nickname 微信昵称
 * @property string $avatar_url 微信头像
 * @property string $province 省
 * @property string $city 市
 * @property string $country 国家
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class UserWechatInfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_wechat_info';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['gender', 'created_at', 'updated_at'], 'integer'],
            [['openid', 'unionid', 'province', 'city', 'country'], 'string', 'max' => 50],
            [['nickname'], 'string', 'max' => 255],
            [['avatar_url'], 'string', 'max' => 300],
            [['openid'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'openid' => 'Openid',
            'unionid' => 'Unionid',
            'gender' => 'Gender',
            'nickname' => 'Nickname',
            'avatar_url' => 'Avatar Url',
            'province' => 'Province',
            'city' => 'City',
            'country' => 'Country',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
