<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "address".
 *
 * @property int $id
 * @property int $uid 用户uid
 * @property string $name 位置名称
 * @property string $city 所在城市
 * @property string $district 市
 * @property string $province 省
 * @property string $address 地址
 * @property string $lat 维度
 * @property string $lng 经度
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 */
class Address extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'address';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'updated_at', 'created_at'], 'integer'],
            [['lat', 'lng'], 'number'],
            [['name', 'address'], 'string', 'max' => 100],
            [['city', 'district', 'province'], 'string', 'max' => 30],
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
            'name' => 'Name',
            'city' => 'City',
            'district' => 'District',
            'province' => 'Province',
            'address' => 'Address',
            'lat' => 'Lat',
            'lng' => 'Lng',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
