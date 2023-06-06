<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "report".
 *
 * @property int $id
 * @property int $uid 举报者uid
 * @property int $report_uid 被举报者uid
 * @property string $report_user_wx 被举报者微信
 * @property int $status 0 未处理 1 已处理
 * @property int $created_at 创建时间
 */
class Report extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'report';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'report_uid', 'status', 'created_at'], 'integer'],
            [['report_user_wx'], 'string', 'max' => 30],
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
            'report_uid' => 'Report Uid',
            'report_user_wx' => 'Report User Wx',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
