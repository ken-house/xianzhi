<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $nickname 昵称
 * @property int $gender 性别（0：保密    1：男    2：女）
 * @property int $birthday 出生日期
 * @property string $avatar 头像
 * @property string $country 国家
 * @property string $province 省区直辖市
 * @property string $city 城市
 * @property int $status 状态（1：正常    0：删除）
 * @property int $invite_uid 我的邀请者uid
 * @property int $invite_at 绑定关系时间
 * @property int $locked_at 锁定时间
 * @property string $locked_reason 锁定原因
 * @property string $phone 登录手机号
 * @property string $wx 微信号
 * @property int $wx_public 微信号是否公开
 * @property string $password 登录密码
 * @property string $wx_openid 微信openid
 * @property int $created_at 创建时间
 * @property int $updated_at 修改时间
 */
class User extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['gender', 'birthday', 'status', 'invite_uid', 'invite_at', 'locked_at', 'wx_public', 'created_at', 'updated_at'], 'integer'],
            [['nickname', 'wx'], 'string', 'max' => 30],
            [['avatar'], 'string', 'max' => 300],
            [['country', 'province', 'city', 'locked_reason', 'wx_openid'], 'string', 'max' => 50],
            [['phone'], 'string', 'max' => 11],
            [['password'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nickname' => 'Nickname',
            'gender' => 'Gender',
            'birthday' => 'Birthday',
            'avatar' => 'Avatar',
            'country' => 'Country',
            'province' => 'Province',
            'city' => 'City',
            'status' => 'Status',
            'invite_uid' => 'Invite Uid',
            'invite_at' => 'Invite At',
            'locked_at' => 'Locked At',
            'locked_reason' => 'Locked Reason',
            'phone' => 'Phone',
            'wx' => 'Wx',
            'wx_public' => 'Wx Public',
            'password' => 'Password',
            'wx_openid' => 'Wx Openid',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
