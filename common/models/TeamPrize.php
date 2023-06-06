<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "team_prize".
 *
 * @property int $id
 * @property string $prize_name 奖品名称
 * @property int $reward_point 奖励积分
 * @property int $uid 中奖用户
 * @property int $period_num 活动期数
 * @property int $updated_at 中奖时间
 * @property int $created_at 创建时间
 */
class TeamPrize extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'team_prize';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['reward_point', 'uid', 'period_num', 'updated_at', 'created_at'], 'integer'],
            [['prize_name'], 'string', 'max' => 10],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'prize_name' => 'Prize Name',
            'reward_point' => 'Reward Point',
            'uid' => 'Uid',
            'period_num' => 'Period Num',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
