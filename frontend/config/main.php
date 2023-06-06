<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-'.YII_ENV.'.php',
    require __DIR__ . '/../../common/config/redis.php',
    require __DIR__ . '/../../common/config/apcu.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-'.YII_ENV.'.php',
    require __DIR__ . '/noAuth.php',
    require __DIR__ . '/noSign.php',
    require __DIR__ . '/noLogin.php'
);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'modules' => [
        'api' => [
            'class' => 'frontend\modules\api\Module',
        ],
    ],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-frontend',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-frontend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'xianzhi-frontend',
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,  //隐藏index.php，需要同时配置nginx的rewrite
            'rules' => [
                //网站首页
                "GET /" => "site/index",
                //基本路径
                "GET,POST,PUT,DELETE <controller:[\w-]+>/<action:[\w-]+>" => "<controller>/<action>",

                //APP内嵌h5页面请求路由规则
                "GET <controller:(app-h5).*>/<action:[\w-]+>" => 'app-h5/index',
                //多层级模块化的路径
                "GET,POST,PUT,DELETE <modules>/<module>/<controller:[\w-]+>/<action:[\w-]+>" => "<modules>/<module>/<controller>/<action>",
                //模块化的路径
                "GET,POST,PUT,DELETE <modules>/<controller:[\w-]+>/<action:[\w-]+>" => "<modules>/<controller>/<action>",

                "GET /<action:contact|copy-right|privacy-policy|service-protocol>.html" => "pindex/<action>",
            ],
        ],
    ],
    'params' => $params,
];
