<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "business_product_record".
 *
 * @property int $id
 * @property int $uid 登录用户uid
 * @property string $business_product_id 商品id
 * @property int $source_id 来源平台 1京东 2拼多多
 * @property string $product_name
 * @property int $type 类型 1 浏览记录 2 加入购物车
 * @property int $updated_at 更新时间
 */
class BusinessProductRecord extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'business_product_record';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'business_product_id', 'source_id', 'type', 'updated_at'], 'integer'],
            [['product_name'], 'string', 'max' => 100],
            [['uid', 'business_product_id', 'source_id', 'type'], 'unique', 'targetAttribute' => ['uid', 'business_product_id', 'source_id', 'type']],
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
            'business_product_id' => 'Business Product ID',
            'source_id' => 'Source ID',
            'product_name' => 'Product Name',
            'type' => 'Type',
            'updated_at' => 'Updated At',
        ];
    }
}
