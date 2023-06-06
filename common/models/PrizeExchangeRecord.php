<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "prize_exchange_record".
 *
 * @property int $id
 * @property int $uid 用户uid
 * @property int $prize_id 奖品id
 * @property int $point 奖品所需积分
 * @property int $status 兑换状态 0 兑换中 1已兑换 2兑换失败
 * @property string $audit_reason 兑换失败原因
 * @property int $audit_at 兑换时间
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 */
class PrizeExchangeRecord extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prize_exchange_record';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'prize_id', 'point', 'status', 'audit_at', 'updated_at', 'created_at'], 'integer'],
            [['audit_reason'], 'string', 'max' => 100],
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
            'prize_id' => 'Prize ID',
            'point' => 'Point',
            'status' => 'Status',
            'audit_reason' => 'Audit Reason',
            'audit_at' => 'Audit At',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
