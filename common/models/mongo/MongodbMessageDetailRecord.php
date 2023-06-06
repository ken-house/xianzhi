<?php

namespace common\models\mongo;

use Yii;

/**
 * This is the model class for collection "message_detail_record".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property mixed                         $type
 * @property mixed                         $sender_uid
 * @property mixed                         $getter_uid
 * @property mixed                         $product_id
 * @property mixed                         $content
 * @property mixed                         $link_url
 * @property mixed                         $status
 * @property mixed                         $created_at
 */
class MongodbMessageDetailRecord extends \yii\mongodb\ActiveRecord
{
    private static $collectionName = null;

    public static function resetTableName($messageKey = '')
    {
        $num = hexdec(substr(md5($messageKey), 30, 2));  //取后两位，16进制转为10进制
        self::$collectionName = 'message_detail_record_' . $num;
    }


    public static function resetTableNameByNum($num)
    {
        self::$collectionName = 'message_detail_record_' . $num;
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
            'sender_uid',
            'getter_uid',
            'product_id',
            'content',
            'link_url',
            'status',
            'created_at',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'sender_uid', 'getter_uid', 'product_id', 'content', 'link_url', 'status', 'created_at'], 'safe']
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
            'sender_uid' => 'Sender Uid',
            'getter_uid' => 'Getter Uid',
            'product_id' => 'Product Id',
            'content' => 'Content',
            'link_url' => 'Link Url',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
