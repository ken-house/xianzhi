<?php
/**
 * 不需要验签的地址
 */
return [
    'noSignUrl' => [
        'api/test/test',
        'api/login/login',
        'api/login/get-wx-openid',
        'api/user/erweima',
        'api/official-account/index', // 公众号服务接口
        'api/business-product/chosen', // 电商精选活动
        'api/pay/notify', // 下单成功回调
        'api/pay/refund-notify', // 退款成功回调
        'api/group-buy/order-erweima',
    ]
];