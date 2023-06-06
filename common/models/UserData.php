<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "user_data".
 *
 * @property int $id
 * @property int $uid 用户id
 * @property string $invite_code 邀请码
 * @property int $invite_friend_num 我邀请的好友个数
 * @property int $latest_login_at 最后一次登录时间
 * @property int $latest_login_ip 最后一次登录ip
 * @property int $login_num 登录次数
 * @property int $last_sign_day 最后一次签到日期
 * @property int $continue_sign_day 连续签到天数
 * @property int $reward_point 当前积分
 * @property int $active_at 活跃时间
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class UserData extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_data';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'invite_friend_num', 'latest_login_at', 'latest_login_ip', 'login_num', 'last_sign_day', 'continue_sign_day', 'reward_point', 'active_at', 'created_at', 'updated_at'], 'integer'],
            [['invite_code'], 'string', 'max' => 10],
            [['uid'], 'unique'],
            [['invite_code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => 'Uid',
            'invite_code' => 'Invite Code',
            'invite_friend_num' => 'Invite Friend Num',
            'latest_login_at' => 'Latest Login At',
            'latest_login_ip' => 'Latest Login Ip',
            'login_num' => 'Login Num',
            'last_sign_day' => 'Last Sign Day',
            'continue_sign_day' => 'Continue Sign Day',
            'reward_point' => 'Reward Point',
            'active_at' => 'Active At',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
