<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "group_buy_product_option".
 *
 * @property int $id
 * @property int $product_id 商品id
 * @property string $name 选项名
 * @property string $price 售价（元）
 * @property string $original_price 原价（元）
 * @property int $max_num 限量份数
 * @property int $sale_num 已售份数
 * @property int $status 是否为有效 1 有效 0 无效
 * @property int $sort 排序
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 */
class GroupBuyProductOption extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'group_buy_product_option';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id', 'max_num', 'sale_num', 'status', 'sort', 'updated_at', 'created_at'], 'integer'],
            [['price', 'original_price'], 'number'],
            [['name'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Product ID',
            'name' => 'Name',
            'price' => 'Price',
            'original_price' => 'Original Price',
            'max_num' => 'Max Num',
            'sale_num' => 'Sale Num',
            'status' => 'Status',
            'sort' => 'Sort',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
