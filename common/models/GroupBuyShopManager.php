<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "group_buy_shop_manager".
 *
 * @property int $id
 * @property int $shop_id 店铺id
 * @property int $uid 店铺管理员uid
 * @property int $role_id 角色 0 普通管理员 1 超级管理员
 * @property int $status 是否有效 0 无效 1 有效
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 */
class GroupBuyShopManager extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'group_buy_shop_manager';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['shop_id', 'uid', 'role_id', 'status', 'updated_at', 'created_at'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_id' => 'Shop ID',
            'uid' => 'Uid',
            'role_id' => 'Role ID',
            'status' => 'Status',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
