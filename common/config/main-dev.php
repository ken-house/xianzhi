<?php

return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'charset' => 'utf8mb4',
            'tablePrefix' => '',
            'masterConfig' => [
                'username' => 'root',
                'password' => 'root',
                'attributes' => [
                    PDO::ATTR_AUTOCOMMIT => 1
                ]
            ],
            'masters' => [
                ['dsn' => 'mysql:host=192.168.214.203;dbname=xianzhi'],
            ],
            'slaveConfig' => [
                'username' => 'root',
                'password' => 'root',
                'attributes' => [
                    PDO::ATTR_AUTOCOMMIT => 1
                ]
            ],
            'slaves' => [
                ['dsn' => 'mysql:host=192.168.214.203;dbname=xianzhi'],
            ],
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 600,
        ],
        'redisBase' => [
            'class' => 'common\components\RedisClient',
            'seeds' => [
                '192.168.214.203:7000',
                '192.168.214.203:7001',
                '192.168.214.203:7002',
                '192.168.214.203:7003',
                '192.168.214.203:7004',
                '192.168.214.203:7005',
            ],
            'timeout' => '1.5',
            'readTimeout' => '1.5',
            'persistent' => true,
        ],
        //geo单机redis
        'redisGeo' => [
            'class' => '\yii\redis\Connection',
            'hostname' => '192.168.214.203',
            'password' => 'redis',
            'port' => 6379,
            'database' => 0,
        ],
        'JWT' => [
            'class' => 'common\components\JWTComponent',
            'certKey' => '1573935603',
        ],
        'es' => [
            'class' => 'common\components\ElasticSearch',
            'hosts' => [
                '192.168.214.203:9200',
            ]
        ],
        //评论mongo
        'mongodbComment' => [
            'class' => '\yii\mongodb\Connection',
            'dsn' => 'mongodb://192.168.214.203:27017/xianzhi_comment',
        ],
        //消息mongo
        'mongodbMessage' => [
            'class' => '\yii\mongodb\Connection',
            'dsn' => 'mongodb://192.168.214.203:27017/xianzhi_message',
        ],

        //消息mongo
        'mongodbRewardPoint' => [
            'class' => '\yii\mongodb\Connection',
            'dsn' => 'mongodb://192.168.214.203:27017/xianzhi_reward_point',
        ],

        //系统消息队列
        'messageQueue' => [
            'class' => 'yii\queue\amqp\Queue',
            'host' => '192.168.214.203',
            'port' => 5672,
            'user' => 'root',
            'password' => 'root',
            'exchangeName' => 'xianzhi.message.exchange',
            'queueName' => 'xianzhi.message.queue',
            'serializer' => '\yii\queue\serializers\JsonSerializer'
        ],

        //积分队列
        'rewardPointQueue' => [
            'class' => 'yii\queue\amqp\Queue',
            'host' => '192.168.214.203',
            'port' => 5672,
            'user' => 'root',
            'password' => 'root',
            'exchangeName' => 'xianzhi.reward_point.exchange',
            'queueName' => 'xianzhi.reward_point.queue',
            'serializer' => '\yii\queue\serializers\JsonSerializer'
        ],


        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => require 'file-log.php',
    ],
];