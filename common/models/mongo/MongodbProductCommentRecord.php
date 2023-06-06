<?php

namespace common\models\mongo;

use Yii;

/**
 * This is the model class for collection "product_comment_record".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property mixed                         $product_id
 * @property mixed                         $comment_id
 * @property mixed                         $uid
 * @property mixed                         $nickname
 * @property mixed                         $avatar
 * @property mixed                         $content
 * @property mixed                         $author
 * @property mixed                         $status
 * @property mixed                         $created_at
 */
class MongodbProductCommentRecord extends \yii\mongodb\ActiveRecord
{
    private static $collectionName = null;

    public static function resetTableName($productId)
    {
        self::$collectionName = 'product_comment_record_' . ($productId % 100);
    }

    public static function resetTableNameByNum($num)
    {
        self::$collectionName = 'product_comment_record_' . $num;
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
            'product_id',
            'comment_id',
            'uid',
            'nickname',
            'avatar',
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
            [['product_id', 'comment_id', 'uid', 'nickname', 'avatar', 'content', 'author', 'status', 'created_at'], 'safe']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'product_id' => 'Product Id',
            'comment_id' => 'Comment Id',
            'uid' => 'Uid',
            'nickname' => 'Nickname',
            'avatar' => 'Avatar',
            'content' => 'Content',
            'author' => 'Author',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
