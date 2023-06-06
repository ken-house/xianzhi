<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "team_list".
 *
 * @property int $id 战队id
 * @property string $team_name 战队名
 * @property int $team_user_num 战队成员数
 * @property int $period_num 活动期数
 * @property int $created_at 创建时间
 */
class TeamList extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'team_list';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['team_user_num', 'period_num', 'created_at'], 'integer'],
            [['team_name'], 'string', 'max' => 10],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'team_name' => 'Team Name',
            'team_user_num' => 'Team User Num',
            'period_num' => 'Period Num',
            'created_at' => 'Created At',
        ];
    }
}
