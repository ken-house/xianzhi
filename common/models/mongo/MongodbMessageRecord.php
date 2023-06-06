<?php

namespace common\models\mongo;

use Yii;

/**
 * This is the model class for collection "message_record".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property mixed                         $type
 * @property mixed                         $content
 * @property mixed                         $delete_uid
 * @property mixed                         $updated_at
 */
class MongodbMessageRecord extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return 'message_record';
    }

    /**
     * @return \yii\mongodb\Connection the MongoDB connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mongodbMessage');
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'type',
            'content',
            'delete_uid',
            'updated_at',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'content', 'delete_uid', 'updated_at'], 'safe']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'type' => 'Type',
            'content' => 'Content',
            'delete_uid' => 'Delete Uid',
            'updated_at' => 'Updated At',
        ];
    }
}
