<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "stick".
 *
 * @property int $id
 * @property int $product_id 商品ID
 * @property string $lat 纬度
 * @property string $lng 经度
 * @property int $start_time 开始时间
 * @property int $end_time 结束时间
 * @property int $status 状态 1 有效 0无效
 * @property int $sort 排序
 * @property int $type 类型 0 首页 1 热门 2 特价 3 活动节日页
 * @property int $activity_id 活动ID
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class Stick extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stick';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id', 'start_time', 'end_time', 'status', 'sort', 'type', 'activity_id', 'created_at', 'updated_at'], 'integer'],
            [['lat', 'lng', 'created_at'], 'required'],
            [['lat', 'lng'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Product ID',
            'lat' => 'Lat',
            'lng' => 'Lng',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'status' => 'Status',
            'sort' => 'Sort',
            'type' => 'Type',
            'activity_id' => 'Activity ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
