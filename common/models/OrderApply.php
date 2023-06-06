<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "order_apply".
 *
 * @property int $id
 * @property int $uid 用户uid
 * @property string $order_sn 订单号
 * @property int $created_at 创建时间
 */
class OrderApply extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_apply';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'created_at'], 'integer'],
            [['order_sn'], 'string', 'max' => 50],
            [['order_sn'], 'unique'],
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
            'order_sn' => 'Order Sn',
            'created_at' => 'Created At',
        ];
    }
}
