<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "product".
 *
 * @property int $id
 * @property int $uid 用户uid
 * @property int $category 分类id
 * @property string $name 商品名称
 * @property string $title 商品标题
 * @property string $info 商品描述
 * @property string $pics 商品图片
 * @property string $tags 商品标签，以分号隔开
 * @property int $category_id 商品分类
 * @property string $price 商品售价（元）
 * @property string $original_price 商品原价（元）
 * @property string $location 商品所在位置
 * @property string $lat 维度
 * @property string $lng 经度
 * @property int $status 审核状态 0 待审核 1 审核通过 2 审核不通过  3已下架  4已删除  5已卖出  
 * @property string $audit_reason 审核原因
 * @property int $audit_at 审核时间
 * @property int $view_num 浏览次数
 * @property int $thumb_num 点赞次数
 * @property int $comment_num 评论次数
 * @property int $want_num 想要人数
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 */
class Product extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'category', 'category_id', 'status', 'audit_at', 'view_num', 'thumb_num', 'comment_num', 'want_num', 'updated_at', 'created_at'], 'integer'],
            [['info', 'pics'], 'required'],
            [['info', 'pics'], 'string'],
            [['price', 'original_price', 'lat', 'lng'], 'number'],
            [['name', 'title', 'location', 'audit_reason'], 'string', 'max' => 50],
            [['tags'], 'string', 'max' => 100],
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
            'category' => 'Category',
            'name' => 'Name',
            'title' => 'Title',
            'info' => 'Info',
            'pics' => 'Pics',
            'tags' => 'Tags',
            'category_id' => 'Category ID',
            'price' => 'Price',
            'original_price' => 'Original Price',
            'location' => 'Location',
            'lat' => 'Lat',
            'lng' => 'Lng',
            'status' => 'Status',
            'audit_reason' => 'Audit Reason',
            'audit_at' => 'Audit At',
            'view_num' => 'View Num',
            'thumb_num' => 'Thumb Num',
            'comment_num' => 'Comment Num',
            'want_num' => 'Want Num',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
