<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "business_product_tmp".
 *
 * @property int $id
 * @property string $business_product_id 电商商品id
 * @property string $title 商品标题
 * @property string $app_id 小程序appid
 * @property string $click_url 商品详情地址
 * @property string $price 商品价格
 * @property string $cash_back_price 返还价格
 * @property int $comment_num 好评数
 * @property int $sale_num 销量
 * @property string $pics 图片
 * @property string $tags 标签
 * @property string $shop_name 商铺名称
 * @property string $search_keyword 关键词
 * @property int $source_id 商家 1 京东  2 拼多多
 * @property int $created_at 创建时间
 */
class BusinessProductTmp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'business_product_tmp';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['business_product_id', 'app_id', 'click_url', 'pics'], 'required'],
            [['business_product_id', 'comment_num', 'sale_num', 'source_id', 'created_at'], 'integer'],
            [['click_url', 'pics'], 'string'],
            [['price', 'cash_back_price'], 'number'],
            [['title', 'tags', 'shop_name'], 'string', 'max' => 100],
            [['app_id'], 'string', 'max' => 30],
            [['search_keyword'], 'string', 'max' => 50],
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
            'app_id' => 'App ID',
            'click_url' => 'Click Url',
            'price' => 'Price',
            'cash_back_price' => 'Cash Back Price',
            'comment_num' => 'Comment Num',
            'sale_num' => 'Sale Num',
            'pics' => 'Pics',
            'tags' => 'Tags',
            'shop_name' => 'Shop Name',
            'search_keyword' => 'Search Keyword',
            'source_id' => 'Source ID',
            'created_at' => 'Created At',
        ];
    }
}
