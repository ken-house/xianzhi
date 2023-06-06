<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "search_keyword".
 *
 * @property int $id
 * @property int $uid 用户uid
 * @property string $keyword 搜索关键词
 * @property int $created_at 创建时间
 */
class SearchKeyword extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'search_keyword';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'created_at'], 'integer'],
            [['keyword'], 'string', 'max' => 100],
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
            'keyword' => 'Keyword',
            'created_at' => 'Created At',
        ];
    }
}
