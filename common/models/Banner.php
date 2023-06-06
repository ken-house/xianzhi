<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "banner".
 *
 * @property int $id
 * @property string $image_url banner图片地址
 * @property string $app_id 小程序appid
 * @property string $link_url 点击跳转地址
 * @property string $lat 所在纬度
 * @property string $lng 所在经度
 * @property string $location 所在位置
 * @property int $start_time 展示开始时间
 * @property int $end_time 展示结束时间
 * @property int $min_version 最小版本号
 * @property int $max_version 最大版本号
 * @property int $status 1 有效  0 删除
 * @property int $sort 排序
 * @property int $type 类型 0 首页 1 热门 2 特价 3 活动节日页
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class Banner extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'banner';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['lat', 'lng'], 'number'],
            [['start_time', 'end_time', 'min_version', 'max_version', 'status', 'sort', 'type', 'created_at', 'updated_at'], 'integer'],
            [['image_url', 'location'], 'string', 'max' => 100],
            [['app_id'], 'string', 'max' => 30],
            [['link_url'], 'string', 'max' => 1000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'image_url' => 'Image Url',
            'app_id' => 'App ID',
            'link_url' => 'Link Url',
            'lat' => 'Lat',
            'lng' => 'Lng',
            'location' => 'Location',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'min_version' => 'Min Version',
            'max_version' => 'Max Version',
            'status' => 'Status',
            'sort' => 'Sort',
            'type' => 'Type',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
