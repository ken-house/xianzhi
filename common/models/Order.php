<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "order".
 *
 * @property int $id
 * @property int $uid 用户uid
 * @property string $order_id 订单唯一标识
 * @property string $order_sn 推广订单号
 * @property int $order_time 下单时间
 * @property int $finish_time 完成时间（确认收货时间）
 * @property int $modify_time 更新时间
 * @property int $settle_date 结算日起
 * @property string $business_product_id 商品id
 * @property string $product_name 商品名称
 * @property string $product_image 商品封面
 * @property int $express_status 发货状态
 * @property string $product_price 商品单价
 * @property int $product_num 商品数量
 * @property string $actual_cos_price 订单计佣金总额
 * @property string $actual_fee 佣金金额
 * @property string $return_fee 返利金额
 * @property int $return_success 是否返利完成 0 未完成 1已完成
 * @property int $status 订单状态
 * @property int $source_id 电商平台 1京东 2拼多多
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 */
class Order extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'order_time', 'finish_time', 'modify_time', 'settle_date', 'business_product_id', 'express_status', 'product_num', 'return_success', 'status', 'source_id', 'updated_at', 'created_at'], 'integer'],
            [['order_sn'], 'required'],
            [['product_price', 'actual_cos_price', 'actual_fee', 'return_fee'], 'number'],
            [['order_id'], 'string', 'max' => 50],
            [['order_sn', 'product_name'], 'string', 'max' => 100],
            [['product_image'], 'string', 'max' => 500],
            [['order_id'], 'unique'],
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
            'order_id' => 'Order ID',
            'order_sn' => 'Order Sn',
            'order_time' => 'Order Time',
            'finish_time' => 'Finish Time',
            'modify_time' => 'Modify Time',
            'settle_date' => 'Settle Date',
            'business_product_id' => 'Business Product ID',
            'product_name' => 'Product Name',
            'product_image' => 'Product Image',
            'express_status' => 'Express Status',
            'product_price' => 'Product Price',
            'product_num' => 'Product Num',
            'actual_cos_price' => 'Actual Cos Price',
            'actual_fee' => 'Actual Fee',
            'return_fee' => 'Return Fee',
            'return_success' => 'Return Success',
            'status' => 'Status',
            'source_id' => 'Source ID',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
