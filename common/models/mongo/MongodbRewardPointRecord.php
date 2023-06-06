<?php

namespace common\models\mongo;

use Yii;

/**
 * This is the model class for collection "reward_point_record".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property mixed                         $uid
 * @property mixed                         $type
 * @property mixed                         $title
 * @property mixed                         $point
 * @property mixed                         $current_point
 * @property mixed                         $created_at
 */
class MongodbRewardPointRecord extends \yii\mongodb\ActiveRecord
{
    private static $collectionName = null;

    public static function resetTableName($uid = 0)
    {
        self::$collectionName = 'reward_point_record_' . $uid % 100;
    }


    public static function resetTableNameByNum($num)
    {
        self::$collectionName = 'reward_point_record_' . $num;
    }

    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return self::$collectionName;
    }

    /**
     * @return \yii\mongodb\Connection the MongoDB connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mongodbRewardPoint');
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'uid',
            'type',
            'title',
            'point',
            'current_point',
            'created_at',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'type', 'title', 'point', 'current_point', 'created_at'], 'safe']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'uid' => 'Uid',
            'type' => 'Type',
            'title' => 'Title',
            'point' => 'Point',
            'current_point' => 'Current Point',
            'created_at' => 'Created At',
        ];
    }
}
