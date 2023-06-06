<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "template_msg_record".
 *
 * @property int $id
 * @property int $buyer_uid 买家uid
 * @property int $saler_uid 卖家uid
 * @property string $product_id 操作对象-商品id
 * @property int $is_read 是否已读
 * @property int $type 类型 1 想要通知 2 卖家回复  3 买家回复  4 审核通过  5审核不通过  6 强制下架  7 返利到账
 * @property string $json_data 其他数据
 * @property int $created_at 创建时间
 */
class TemplateMsgRecord extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'template_msg_record';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['buyer_uid', 'saler_uid', 'is_read', 'type', 'created_at'], 'integer'],
            [['product_id'], 'string', 'max' => 100],
            [['json_data'], 'string', 'max' => 1000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'buyer_uid' => 'Buyer Uid',
            'saler_uid' => 'Saler Uid',
            'product_id' => 'Product ID',
            'is_read' => 'Is Read',
            'type' => 'Type',
            'json_data' => 'Json Data',
            'created_at' => 'Created At',
        ];
    }
}
