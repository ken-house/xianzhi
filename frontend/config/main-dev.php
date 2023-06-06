<?php

$config = [
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'FaABdU5pMNGWEZb14ve_CJ4dJWYu_5LT',
        ],
    ],
];
if (!YII_ENV_PROD) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['127.0.0.1', '::1', '192.168.10.*', '192.168.1.*', '192.168.214.*', '0.0.0.0','10.0.10.*'],
        'generators' => [
            'job' => [
                'class' => \yii\queue\gii\Generator::class,
            ],
            'mongoDbModel' => [
                'class' => \yii\mongodb\gii\model\Generator::class,
            ],
        ]
    ];

}

return $config;
