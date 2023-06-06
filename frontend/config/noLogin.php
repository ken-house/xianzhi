<?php
/**
 * 不登录也能访问的地址
 */
return [
    'noLoginUrl' => [
        'api/index/index',
        'api/index/product-list',
        'api/product/business',
        'api/product/business-recommend',
        'api/product/info',
        'api/product/category',
        'api/product/business-product-click',
        'api/category/product-list',
        'api/category/nav-product-list',
        'api/category/index',
        'api/mycenter/visit',
        'api/product/comment-list',
        'api/message/red-point',
        'api/index/notice',
        'api/rank/point',
        'api/clock/index',
        'api/clock/info',
        'api/clock/visit',
        'api/team/index',
        'api/team/team-info',
        'api/team/team-list',
        'api/team/prize',
        'api/team/notice',
        'api/business-product/click',
        'api/business-product/chosen', // 电商精选活动
        'api/parttime-job/info',
        'api/parttime-job/index',
        'api/pay/notify', // 下单成功回调
        'api/pay/refund-notify', // 退款成功回调
        'api/group-buy/index',
        'api/group-buy/info',
        'api/group-buy/shop',
        'api/group-buy/dsp-info',




        // 广告接口
        'api/ad/index', // 首页广告接口
        'api/ad/index-product', // 热门、推荐广告接口
        'api/ad/product-info', // 宝贝详情广告接口
        'api/ad/message-list', // 消息列表广告接口
        'api/ad/message-system', // 系统消息广告接口
        'api/ad/category', // 分类页广告接口
        'api/ad/mycenter', // 我的页面广告接口
        'api/ad/mycenter-visit', // 我的主页广告接口
        'api/ad/mycenter-product', // 我的发布广告接口
        'api/ad/product-list', // 我的发布广告接口
        'api/ad/wheel', // 大转盘广告接口
        'api/ad/task', // 大转盘广告接口
        'api/ad/job-info', // 兼职详情广告接口
    ]
];