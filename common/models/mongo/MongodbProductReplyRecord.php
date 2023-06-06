<?php

namespace common\models\mongo;

use Yii;

/**
 * This is the model class for collection "product_reply_record".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property mixed                         $uid
 * @property mixed                         $product_id
 * @property mixed                         $comment_id
 * @property mixed                         $reply_id
 * @property mixed                         $nickname
 * @property mixed                         $avatar
 * @property mixed                         $notice_users
 * @property mixed                         $content
 * @property mixed                         $author
 * @property mixed                         $status
 * @property mixed                         $created_at
 */
class MongodbProductReplyRecord extends \yii\mongodb\ActiveRecord
{
    private static $collectionName = null;

    public static function resetTableName($commentId = '')
    {
        $num = hexdec(substr($commentId, 30, 2));  //取后两位，16进制转为10进制
        self::$collectionName = 'product_reply_record_' . $num;
    }


    public static function resetTableNameByNum($num)
    {
        self::$collectionName = 'product_reply_record_' . $num;
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
        return Yii::$app->get('mongodbComment');
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'uid',
            'product_id',
            'comment_id',
            'reply_id',
            'nickname',
            'avatar',
            'notice_users',
            'content',
            'author',
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
            [['uid', 'product_id', 'comment_id', 'reply_id', 'nickname', 'avatar', 'notice_users', 'content', 'author', 'status', 'created_at'], 'safe']
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
            'product_id' => 'Product Key',
            'comment_id' => 'Comment Id',
            'reply_id' => 'Reply Id',
            'nickname' => 'Nickname',
            'avatar' => 'Avatar',
            'notice_users' => 'Notice Users',
            'content' => 'Content',
            'author' => 'Author',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
