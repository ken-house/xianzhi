<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "group_buy_product".
 *
 * @property int $id
 * @property string $title 标题
 * @property string $sub_title 小标题
 * @property string $price 售价（元）
 * @property string $original_price 原价（元）
 * @property int $refund_type 退款类型 0 不支持退款 1 随时退款  3 过期自动退款
 * @property string $pics 详情图片
 * @property int $start_at 活动开始时间
 * @property int $end_at 活动结束时间
 * @property int $max_num 限量份数
 * @property int $max_user_buy_num 每人最多购买份数
 * @property int $sale_num 已售份数
 * @property int $shop_id 商家id
 * @property string $info 套餐详情
 * @property string $rule_info 使用说明
 * @property int $status 审核状态 0 待上架 1 上架  2 已下架
 * @property int $category_id 分类
 * @property int $sort 排序
 * @property int $view_num 浏览数
 * @property int $comment_num 评论数
 * @property int $is_distribute 是否为分销商品 1是 0不是
 * @property string $commission_rate 佣金比例
 * @property string $shop_commission_rate 商家佣金比例
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 */
class GroupBuyProduct extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'group_buy_product';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['price', 'original_price', 'commission_rate', 'shop_commission_rate'], 'number'],
            [['refund_type', 'start_at', 'end_at', 'max_num', 'max_user_buy_num', 'sale_num', 'shop_id', 'status', 'category_id', 'sort', 'view_num', 'comment_num', 'is_distribute', 'updated_at', 'created_at'], 'integer'],
            [['pics', 'info', 'rule_info'], 'required'],
            [['pics', 'info', 'rule_info'], 'string'],
            [['title', 'sub_title'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'sub_title' => 'Sub Title',
            'price' => 'Price',
            'original_price' => 'Original Price',
            'refund_type' => 'Refund Type',
            'pics' => 'Pics',
            'start_at' => 'Start At',
            'end_at' => 'End At',
            'max_num' => 'Max Num',
            'max_user_buy_num' => 'Max User Buy Num',
            'sale_num' => 'Sale Num',
            'shop_id' => 'Shop ID',
            'info' => 'Info',
            'rule_info' => 'Rule Info',
            'status' => 'Status',
            'category_id' => 'Category ID',
            'sort' => 'Sort',
            'view_num' => 'View Num',
            'comment_num' => 'Comment Num',
            'is_distribute' => 'Is Distribute',
            'commission_rate' => 'Commission Rate',
            'shop_commission_rate' => 'Shop Commission Rate',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }
}
