<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "team_activity".
 *
 * @property int $id
 * @property int $period_num 活动期数
 * @property int $start_at 活动开始时间
 * @property int $end_at 活动结束时间
 * @property int $reward_point 活动奖金池
 * @property int $user_num 参与人数
 * @property int $updated_at 活动数据更新时间
 * @property int $created_at 活动创建时间
 */
class TeamActivity extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'team_activity';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['period_num', 'start_at', 'end_at', 'reward_point', 'user_num', 'updated_at', 'created_at'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'period_num' => 'Period Num',
            'start_at' => 'Start At',
            'end_at' => 'End At',
            'reward_point' => 'Reward Point',
            'user_num' => 'User Num',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
