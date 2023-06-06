<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "business_product_new".
 *
 * @property int $id
 * @property string $business_product_id 电商商品id
 * @property string $title 商品标题
 * @property string $short_url 商品详情地址
 * @property string $click_url 长链接
 * @property string $price 商品价格
 * @property string $cash_back_price 返还价格
 * @property int $comment_num 好评数
 * @property int $sale_num 销量
 * @property int $click_num 点击数
 * @property string $pics 图片
 * @property string $tags 标签
 * @property string $shop_name 商铺名称
 * @property int $category 分类
 * @property int $source_id 商家 1 京东  2 拼多多
 * @property int $status 状态  1 启用  0不启用
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 */
class BusinessProductNew extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'business_product_new';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['business_product_id', 'click_url', 'pics'], 'required'],
            [['business_product_id', 'comment_num', 'sale_num', 'click_num', 'category', 'source_id', 'status', 'updated_at', 'created_at'], 'integer'],
            [['click_url', 'pics'], 'string'],
            [['price', 'cash_back_price'], 'number'],
            [['title', 'short_url', 'tags', 'shop_name'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'business_product_id' => 'Business Product ID',
            'title' => 'Title',
            'short_url' => 'Short Url',
            'click_url' => 'Click Url',
            'price' => 'Price',
            'cash_back_price' => 'Cash Back Price',
            'comment_num' => 'Comment Num',
            'sale_num' => 'Sale Num',
            'click_num' => 'Click Num',
            'pics' => 'Pics',
            'tags' => 'Tags',
            'shop_name' => 'Shop Name',
            'category' => 'Category',
            'source_id' => 'Source ID',
            'status' => 'Status',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
