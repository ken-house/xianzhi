<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "group_buy_shop".
 *
 * @property int $id
 * @property string $shop_name 店铺名称
 * @property string $open_time 营业时间
 * @property string $phone 联系电话
 * @property string $location 店铺位置
 * @property string $lat 维度
 * @property string $lng 经度
 * @property string $shop_avatar 店铺头像
 * @property string $shop_logo 店铺logo
 * @property string $info 店铺介绍
 * @property string $pics 图片
 * @property string $avg_price 人均价格（元）
 * @property string $score 店铺评分
 * @property int $order_num 总有效订单数
 * @property string $commission_rate 佣金比例
 * @property string $total_income 预计总收入（元）
 * @property string $settle_amount 已结算金额（元）
 * @property string $withdraw_amount 已提现金额（元）
 * @property string $withdraw_account 提现账户
 * @property int $status 是否合作中 1是 0不是
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 */
class GroupBuyShop extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'group_buy_shop';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['lat', 'lng', 'avg_price', 'score', 'commission_rate', 'total_income', 'settle_amount', 'withdraw_amount'], 'number'],
            [['info', 'pics'], 'required'],
            [['info', 'pics'], 'string'],
            [['order_num', 'status', 'updated_at', 'created_at'], 'integer'],
            [['shop_name', 'open_time', 'phone'], 'string', 'max' => 50],
            [['location', 'shop_avatar', 'shop_logo', 'withdraw_account'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_name' => 'Shop Name',
            'open_time' => 'Open Time',
            'phone' => 'Phone',
            'location' => 'Location',
            'lat' => 'Lat',
            'lng' => 'Lng',
            'shop_avatar' => 'Shop Avatar',
            'shop_logo' => 'Shop Logo',
            'info' => 'Info',
            'pics' => 'Pics',
            'avg_price' => 'Avg Price',
            'score' => 'Score',
            'order_num' => 'Order Num',
            'commission_rate' => 'Commission Rate',
            'total_income' => 'Total Income',
            'settle_amount' => 'Settle Amount',
            'withdraw_amount' => 'Withdraw Amount',
            'withdraw_account' => 'Withdraw Account',
            'status' => 'Status',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
