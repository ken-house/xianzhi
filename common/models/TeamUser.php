<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "team_user".
 *
 * @property int $id
 * @property int $uid 参与用户uid
 * @property int $invite_uid 邀请参与用户uid
 * @property string $nickname 昵称
 * @property int $gender 性别
 * @property string $avatar 头像
 * @property int $team_id 战队id
 * @property int $period_num 活动期数
 * @property int $reward_point 贡献积分
 * @property int $created_at 加入时间
 */
class TeamUser extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'team_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'invite_uid', 'gender', 'team_id', 'period_num', 'reward_point', 'created_at'], 'integer'],
            [['gender'], 'required'],
            [['nickname'], 'string', 'max' => 30],
            [['avatar'], 'string', 'max' => 300],
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
            'invite_uid' => 'Invite Uid',
            'nickname' => 'Nickname',
            'gender' => 'Gender',
            'avatar' => 'Avatar',
            'team_id' => 'Team ID',
            'period_num' => 'Period Num',
            'reward_point' => 'Reward Point',
            'created_at' => 'Created At',
        ];
    }
}
