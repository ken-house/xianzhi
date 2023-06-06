<?php

return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'charset' => 'utf8mb4',
            'tablePrefix' => '',
            'masterConfig' => [
                'username' => 'xianzhi',
                'password' => 'fkieTvJYdF7WSCm%V',
                'attributes' => [
                    PDO::ATTR_AUTOCOMMIT => 1
                ]
            ],
            'masters' => [
                ['dsn' => 'mysql:host=172.17.0.13;dbname=xianzhi'],
            ],
            'slaveConfig' => [
                'username' => 'xianzhi',
                'password' => 'fkieTvJYdF7WSCm%V',
                'attributes' => [
                    PDO::ATTR_AUTOCOMMIT => 1
                ]
            ],
            'slaves' => [
                ['dsn' => 'mysql:host=172.17.0.13;dbname=xianzhi'],
            ],
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 600,
        ],
        //基础业务（用户相关）
        'redisBase' => [
            'class' => 'common\components\RedisClient',
            'seeds' => [
                '172.17.0.13:7000',
                '172.17.0.13:7001',
                '172.17.0.13:7002',
                '172.17.0.13:7003',
                '172.17.0.13:7004',
                '172.17.0.13:7005',
            ],
            'timeout' => '1.5',
            'readTimeout' => '1.5',
            'persistent' => true,
        ],
        //geo单机redis
        'redisGeo' => [
            'class' => '\yii\redis\Connection',
            'hostname' => '172.17.0.13',
            'password' => 'S2HX1tUqEkDfmzng',
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
                '172.17.0.13:9200',
            ]
        ],
        //评论mongo
        'mongodbComment' => [
            'class' => '\yii\mongodb\Connection',
            'dsn' => 'mongodb://xianzhi_mongo:oFMVrTDliQPHdONW@172.17.0.13:27017/xianzhi_comment',
        ],
        //消息mongo
        'mongodbMessage' => [
            'class' => '\yii\mongodb\Connection',
            'dsn' => 'mongodb://xianzhi_mongo:oFMVrTDliQPHdONW@172.17.0.13:27017/xianzhi_message',
        ],

        //积分记录mongo
        'mongodbRewardPoint' => [
            'class' => '\yii\mongodb\Connection',
            'dsn' => 'mongodb://xianzhi_mongo:oFMVrTDliQPHdONW@172.17.0.13:27017/xianzhi_reward_point',
        ],

        //系统消息队列
        'messageQueue' => [
            'class' => 'yii\queue\amqp\Queue',
            'host' => '172.17.0.13',
            'port' => 5672,
            'user' => 'root',
            'password' => 'pfWL4jkwvDhGyKiY',
            'exchangeName' => 'xianzhi.message.exchange',
            'queueName' => 'xianzhi.message.queue',
            'serializer' => '\yii\queue\serializers\JsonSerializer'
        ],

        //积分队列
        'rewardPointQueue' => [
            'class' => 'yii\queue\amqp\Queue',
            'host' => '172.17.0.13',
            'port' => 5672,
            'user' => 'root',
            'password' => 'pfWL4jkwvDhGyKiY',
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
