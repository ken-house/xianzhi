<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "group_buy_dsp".
 *
 * @property int $id
 * @property int $uid 用户uid
 * @property string $invite_code 邀请码
 * @property int $order_num 有效订单数量
 * @property string $total_income 预计总收入（元）
 * @property string $settle_amount 已结算金额（元）
 * @property string $withdraw_amount 已提现金额（元）
 * @property int $status 审核状态 0 待审核 1 审核通过 2 审核不通过
 * @property string $audit_reason 审核不通过原因
 * @property int $audit_at 审核时间
 * @property int $open_at 开店时间
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 */
class GroupBuyDsp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'group_buy_dsp';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'order_num', 'status', 'audit_at', 'open_at', 'updated_at', 'created_at'], 'integer'],
            [['total_income', 'settle_amount', 'withdraw_amount'], 'number'],
            [['invite_code'], 'string', 'max' => 10],
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
            'invite_code' => 'Invite Code',
            'order_num' => 'Order Num',
            'total_income' => 'Total Income',
            'settle_amount' => 'Settle Amount',
            'withdraw_amount' => 'Withdraw Amount',
            'status' => 'Status',
            'audit_reason' => 'Audit Reason',
            'audit_at' => 'Audit At',
            'open_at' => 'Open At',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
