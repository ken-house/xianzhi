<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "group_buy_withdraw".
 *
 * @property int $id
 * @property int $uid 用户uid
 * @property int $shop_id 店铺id
 * @property string $amount 提现金额（元）
 * @property string $current_amount 可提现余额（元）
 * @property int $status 审核状态 0 待审核 1 审核通过 2 审核不通过 3 已打款
 * @property string $audit_reason 审核不通过原因
 * @property int $audit_at 审核时间
 * @property int $pay_at 打款时间
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 */
class GroupBuyWithdraw extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'group_buy_withdraw';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'shop_id', 'status', 'audit_at', 'pay_at', 'updated_at', 'created_at'], 'integer'],
            [['amount', 'current_amount'], 'number'],
            [['audit_reason'], 'string', 'max' => 50],
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
            'shop_id' => 'Shop ID',
            'amount' => 'Amount',
            'current_amount' => 'Current Amount',
            'status' => 'Status',
            'audit_reason' => 'Audit Reason',
            'audit_at' => 'Audit At',
            'pay_at' => 'Pay At',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
