<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "parttime_apply".
 *
 * @property int $id
 * @property int $uid 报名用户
 * @property int $job_id 兼职工作id
 * @property int $status 1 有效 0无效
 * @property int $created_at 创建时间
 * @property int $updated_at 修改时间
 */
class ParttimeApply extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parttime_apply';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'job_id', 'status', 'created_at', 'updated_at'], 'integer'],
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
            'job_id' => 'Job ID',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
