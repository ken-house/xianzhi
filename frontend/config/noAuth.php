<?php
/**
 * 不验证登录的地址
 */
return [
    'noAuthUrl' => [
        'api/test/test',
        'api/login/index',
        'api/login/login',
        'api/login/get-wx-state',
        'api/login/get-wx-openid',
        'api/user/erweima',
        'api/official-account/index', // 公众号服务接口
        'api/group-buy/order-erweima',
    ]
];