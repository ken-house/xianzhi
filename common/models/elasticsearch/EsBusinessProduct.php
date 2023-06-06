<?php
/**
 * 商品ES
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/1/7 14:52
 */

namespace common\models\elasticsearch;

class EsBusinessProduct extends EsActiveRecord
{
    public static function attributes()
    {
        return [
            'id',
            'business_product_id',
            'title',
            'url',
            'discount_url',
            'price',
            'cash_back_price',
            'click_num',
            'comment_num',
            'sale_num',
            'pics',
            'tags',
            'shop_name',
            'category_id',
            'category_name',
            'source_id',
            'status',
            'updated_at',
            'created_at',
        ];
    }

    public static function index()
    {
        return 'xianzhi_business_product';
    }

    /**
     * mapping
     *
     * @return array|mixed
     * @author   xudt
     * @dateTime 2020/3/8 16:36
     */
    public static function mapping()
    {
        return [
            'dynamic' => "false",
            'properties' => [
                'id' => ['type' => 'keyword', 'index' => false],
                'business_product_id' => ['type' => 'keyword', 'index' => false],
                'title' => ['type' => 'text', 'analyzer' => 'ik_max_word', 'search_analyzer' => 'ik_max_word'],
                'url' => ['type' => 'keyword', 'index' => false],
                'discount_url' => ['type' => 'keyword', 'index' => false],
                'price' => ['type' => 'float'],
                'cash_back_price' => ['type' => 'float'],
                'click_num' => ['type' => 'integer'],
                'comment_num' => ['type' => 'integer'],
                'sale_num' => ['type' => 'integer'],
                'pics' => ['type' => 'keyword', 'index' => false],
                'tags' => ['type' => 'keyword', 'index' => false],
                'shop_name' => ['type' => 'keyword', 'index' => false],
                'category_id' => ['type' => 'integer'],
                'category_name' => ['type' => 'text', 'analyzer' => 'ik_max_word', 'search_analyzer' => 'ik_max_word'],
                'source_id' => ['type' => 'integer'],
                'status' => ['type' => 'integer'],
                'updated_at' => ['type' => 'integer'],
                'created_at' => ['type' => 'integer'],
            ]
        ];
    }
}