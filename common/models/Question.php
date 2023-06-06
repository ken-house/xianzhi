<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "question".
 *
 * @property int $id
 * @property string $title 题目标题
 * @property string $answer_list 选择列表
 * @property int $answer 正确答案
 * @property string $info 答案解析
 * @property int $status 是否有效
 * @property int $created_at 创建时间
 */
class Question extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'question';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['answer', 'status', 'created_at'], 'integer'],
            [['info'], 'required'],
            [['info'], 'string'],
            [['title', 'answer_list'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'answer_list' => 'Answer List',
            'answer' => 'Answer',
            'info' => 'Info',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
