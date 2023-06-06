<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "product_category_hot".
 *
 * @property int $id
 * @property string $category_name 分类名称
 * @property int $category_id 分类id
 * @property string $icon 分类图标
 * @property int $pid 分类父节点
 * @property int $category_level 分类层级
 * @property int $status 状态 1 显示  0 不显示
 * @property int $sort 排序
 * @property int $created_at 创建时间
 */
class ProductCategoryHot extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_category_hot';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_id', 'pid', 'category_level', 'status', 'sort', 'created_at'], 'integer'],
            [['category_name'], 'string', 'max' => 10],
            [['icon'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_name' => 'Category Name',
            'category_id' => 'Category ID',
            'icon' => 'Icon',
            'pid' => 'Pid',
            'category_level' => 'Category Level',
            'status' => 'Status',
            'sort' => 'Sort',
            'created_at' => 'Created At',
        ];
    }
}
