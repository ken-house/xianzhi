<?php
/**
 * apcu key定义
 * @author xudt
 * @date   : 2019/12/7 11:50
 */
return [
    'apcu.config' => [
        /**
         * 类型：Array
         * 功能：banner列表
         * 过期时间：1小时
         * KEY参数：
         *      第一个参数为位置类型 0 首页 1 热门专区 2 免费专区 3 宠物领养  4 房产出租
         *      第一个参数为周边的bannerId列表以-间隔
         */
        'bannerList' => 'banner_list:%s:%s',

        /**
         * 类型：Array
         * 功能：电商商品列表
         * 过期时间：3天 - 5天
         * KEY参数：
         *      第一个参数为来源 1 京东 2 拼多多
         *      第二个参数为搜索关键词
         *      第三个参数为页码
         */
        'businessProductList' => 'business_product_list_1:%s:%s:%s',

        /**
         * 类型：Array
         * 功能：电商商品推荐列表
         * 过期时间：1 天
         * KEY参数：
         *      第一个参数为来源 1 京东 2 拼多多
         *      第二个参数为频道
         *      第三个参数为页码
         */
        'businessRecommendProductList' => 'business_recommend_product_list_1:%s:%s:%s',

        /**
         * 类型：Array
         * 功能：分类列表
         * 过期时间：1小时
         * KEY参数：
         *      第一个参数为pid
         *      第二个参数为hot
         *      第三个参数为children
         */
        'categoryList' => 'category_list:%s:%s:%s',
    ]
];