<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "parttime_job".
 *
 * @property int $id
 * @property int $uid 发布者uid
 * @property string $title 职位
 * @property int $category_id 职位分类
 * @property int $people_num 招聘人数
 * @property string $lat 纬度
 * @property string $lng 经度
 * @property string $location 工作地点
 * @property string $info 工作内容
 * @property string $pics 工作图片
 * @property int $settle_type 结算方式
 * @property int $salary 工资
 * @property int $status 审核状态 0 待审核 1 审核通过 2 审核不通过  3已删除
 * @property string $audit_reason 审核原因
 * @property int $audit_at 审核时间
 * @property int $view_num 浏览次数
 * @property int $apply_num 申请人数
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class ParttimeJob extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parttime_job';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'category_id', 'people_num', 'settle_type', 'salary', 'status', 'audit_at', 'view_num', 'apply_num', 'created_at', 'updated_at'], 'integer'],
            [['lat', 'lng'], 'number'],
            [['info', 'pics'], 'required'],
            [['info', 'pics'], 'string'],
            [['title', 'location', 'audit_reason'], 'string', 'max' => 50],
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
            'title' => 'Title',
            'category_id' => 'Category ID',
            'people_num' => 'People Num',
            'lat' => 'Lat',
            'lng' => 'Lng',
            'location' => 'Location',
            'info' => 'Info',
            'pics' => 'Pics',
            'settle_type' => 'Settle Type',
            'salary' => 'Salary',
            'status' => 'Status',
            'audit_reason' => 'Audit Reason',
            'audit_at' => 'Audit At',
            'view_num' => 'View Num',
            'apply_num' => 'Apply Num',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
