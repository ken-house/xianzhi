<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "wechat_owner".
 *
 * @property int $id
 * @property string $wx 微信号
 * @property int $type 所属行业 0 普通用户  1 销售人员  2 私营 3 微商  999 黑名单
 * @property string $province 所在省份
 * @property string $city 所在城市
 * @property string $area 所在区县
 * @property string $town 所在镇
 * @property int $enabled 是否有效
 * @property int $created_at 创建时间
 */
class WechatOwner extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'wechat_owner';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'enabled', 'created_at'], 'integer'],
            [['wx'], 'string', 'max' => 30],
            [['province', 'city', 'area', 'town'], 'string', 'max' => 10],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'wx' => 'Wx',
            'type' => 'Type',
            'province' => 'Province',
            'city' => 'City',
            'area' => 'Area',
            'town' => 'Town',
            'enabled' => 'Enabled',
            'created_at' => 'Created At',
        ];
    }
}
