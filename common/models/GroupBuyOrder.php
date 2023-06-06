<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "group_buy_order".
 *
 * @property int $id
 * @property string $order_no 订单号
 * @property string $order_amount 订单金额（元）
 * @property string $shop_commission_rate 商家佣金比例
 * @property string $shop_order_amount 商家订单金额（元）
 * @property string $order_price 订单单价（元）
 * @property int $order_num 订单数量
 * @property int $refund_type 退款类型 0 不支持退款 1 随时退款  3 过期自动退款
 * @property string $remark 订单备注
 * @property int $product_id 团购商品id
 * @property int $option_id 商品购买项id
 * @property string $option_name 商品购买项名称
 * @property string $product_title 标题
 * @property string $product_cover 封面图片
 * @property int $uid 用户id
 * @property int $shop_id 店铺id
 * @property int $is_distribute 是否为分享订单 1是 0不是
 * @property int $dsp_uid 分销用户id
 * @property string $commission_rate 佣金比例
 * @property string $commission_amount 预估佣金
 * @property int $status 订单状态 0 待支付 1 已支付 2 已核销 3 申请退款 4 退款完成 5 订单关闭
 * @property int $delete_status 是否为删除订单 1是 0不是
 * @property int $delete_at 删除时间
 * @property int $pay_at 支付时间
 * @property int $finish_uid 核销管理员
 * @property int $finish_at 核销时间
 * @property string $refund_order_no 退款订单号
 * @property string $refund_amount 退款金额（元）
 * @property int $refund_apply_at 申请退款时间
 * @property int $refund_audit_at 审核退款时间
 * @property string $refund_fail_reason 退款失败原因
 * @property int $refund_finish_at 退款完成时间
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 */
class GroupBuyOrder extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'group_buy_order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_amount', 'shop_commission_rate', 'shop_order_amount', 'order_price', 'commission_rate', 'commission_amount', 'refund_amount'], 'number'],
            [['order_num', 'refund_type', 'product_id', 'option_id', 'uid', 'shop_id', 'is_distribute', 'dsp_uid', 'status', 'delete_status', 'delete_at', 'pay_at', 'finish_uid', 'finish_at', 'refund_apply_at', 'refund_audit_at', 'refund_finish_at', 'updated_at', 'created_at'], 'integer'],
            [['order_no', 'option_name', 'product_title', 'refund_order_no'], 'string', 'max' => 50],
            [['remark', 'refund_fail_reason'], 'string', 'max' => 60],
            [['product_cover'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_no' => 'Order No',
            'order_amount' => 'Order Amount',
            'shop_commission_rate' => 'Shop Commission Rate',
            'shop_order_amount' => 'Shop Order Amount',
            'order_price' => 'Order Price',
            'order_num' => 'Order Num',
            'refund_type' => 'Refund Type',
            'remark' => 'Remark',
            'product_id' => 'Product ID',
            'option_id' => 'Option ID',
            'option_name' => 'Option Name',
            'product_title' => 'Product Title',
            'product_cover' => 'Product Cover',
            'uid' => 'Uid',
            'shop_id' => 'Shop ID',
            'is_distribute' => 'Is Distribute',
            'dsp_uid' => 'Dsp Uid',
            'commission_rate' => 'Commission Rate',
            'commission_amount' => 'Commission Amount',
            'status' => 'Status',
            'delete_status' => 'Delete Status',
            'delete_at' => 'Delete At',
            'pay_at' => 'Pay At',
            'finish_uid' => 'Finish Uid',
            'finish_at' => 'Finish At',
            'refund_order_no' => 'Refund Order No',
            'refund_amount' => 'Refund Amount',
            'refund_apply_at' => 'Refund Apply At',
            'refund_audit_at' => 'Refund Audit At',
            'refund_fail_reason' => 'Refund Fail Reason',
            'refund_finish_at' => 'Refund Finish At',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
