<?php
/**
 * 商品ES
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/1/7 14:52
 */

namespace common\models\elasticsearch;

class EsProduct extends EsActiveRecord
{
    public static function attributes()
    {
        return [
            'id',
            'uid',
            'nickname',
            'avatar',
            'gender',
            'name',
            'title',
            'category_id',
            'category_name',
            'info',
            'pics',
            'tags',
            'price',
            'original_price',
            'cut_price',
            'location',
            'lat',
            'lng',
            'status',
            'view_num',
            'thumb_num',
            'comment_num',
            'want_num',
            'updated_at',
            'created_at',
        ];
    }

    public static function index()
    {
        return 'xianzhi_product';
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
                'id' => ['type' => 'keyword'],
                'uid' => ['type' => 'keyword'],
                'nickname' => ['type' => 'keyword'],
                'avatar' => ['type' => 'keyword', 'index' => false],
                'gender' => ['type' => 'integer', 'index' => false],
                'name' => ['type' => 'text', 'analyzer' => 'ik_max_word', 'search_analyzer' => 'ik_max_word'],
                'title' => ['type' => 'text', 'analyzer' => 'ik_max_word', 'search_analyzer' => 'ik_max_word'],
                'category_id' => ['type' => 'integer'],
                'category_name' => ['type' => 'text', 'analyzer' => 'ik_max_word', 'search_analyzer' => 'ik_max_word'],
                'info' => ['type' => 'text', 'analyzer' => 'ik_max_word', 'search_analyzer' => 'ik_max_word'],
                'pics' => ['type' => 'keyword', 'index' => false],
                'tags' => ['type' => 'keyword', 'index' => false],
                'price' => ['type' => 'float'],
                'original_price' => ['type' => 'keyword', 'index' => false],
                'cut_price' => ['type' => 'keyword', 'index' => false],
                'location' => ['type' => 'keyword', 'index' => false],
                'lat' => ['type' => 'keyword', 'index' => false],
                'lng' => ['type' => 'keyword', 'index' => false],
                'status' => ['type' => 'integer'],
                'view_num' => ['type' => 'integer'],
                'thumb_num' => ['type' => 'integer'],
                'comment_num' => ['type' => 'integer'],
                'want_num' => ['type' => 'integer'],
                'updated_at' => ['type' => 'integer'],
                'created_at' => ['type' => 'integer'],
            ]
        ];
    }
}