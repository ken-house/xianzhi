<?php

return [
    'traceLevel' => YII_DEBUG ? 3 : 0,
    'targets' => [
        [
            'class' => 'notamedia\sentry\SentryTarget',
            'dsn' => 'https://9139fa432dee4be0a12f3a4ba0d66ff9@o435470.ingest.sentry.io/5816823',
            'levels' => ['error'],
            'context' => false
        ],
        [
            'class' => 'yii\log\FileTarget',
            'levels' => ['info'],
            'categories' => ['sendRequest'],
            'logFile' => '@app/runtime/logs/sendRequest/' . date('Y-m-d') . '.log',
            'logVars' => ['_FILES'],
        ],
        [
            'class' => 'yii\log\FileTarget',
            'levels' => ['info'],
            'categories' => ['inviteFriend'],
            'logFile' => '@app/runtime/logs/inviteFriend/' . date('Y-m-d') . '.log',
            'logVars' => ['_FILES'],
        ],
        [
            'class' => 'yii\log\FileTarget',
            'levels' => ['info'],
            'categories' => ['messageSendConsumer'],
            'logFile' => '@app/runtime/logs/messageSendConsumer/' . date('Y-m-d') . '.log',
            'logVars' => ['_FILES'],
        ],
        [
            'class' => 'yii\log\FileTarget',
            'levels' => ['info'],
            'categories' => ['rewardPointConsumer'],
            'logFile' => '@app/runtime/logs/rewardPointConsumer/' . date('Y-m-d') . '.log',
            'logVars' => ['_FILES'],
        ],
        [
            'class' => 'yii\log\FileTarget',
            'levels' => ['info'],
            'categories' => ['rewardPoint'],
            'logFile' => '@app/runtime/logs/rewardPoint/' . date('Y-m-d') . '.log',
            'logVars' => ['_FILES'],
        ],
        [
            'class' => 'yii\log\FileTarget',
            'levels' => ['info'],
            'categories' => ['autoPass'],
            'logFile' => '@app/runtime/logs/autoPass/' . date('Y-m-d') . '.log',
            'logVars' => ['_FILES'],
        ],
        [
            'class' => 'yii\log\FileTarget',
            'levels' => ['info'],
            'categories' => ['autoPassClock'],
            'logFile' => '@app/runtime/logs/autoPassClock/' . date('Y-m-d') . '.log',
            'logVars' => ['_FILES'],
        ],
        [
            'class' => 'yii\log\FileTarget',
            'levels' => ['info'],
            'categories' => ['imageNotFound'],
            'logFile' => '@app/runtime/logs/imageNotFound/' . date('Y-m-d') . '.log',
            'logVars' => ['_FILES'],
        ],
        [
            'class' => 'yii\log\FileTarget',
            'levels' => ['info'],
            'categories' => ['signError'],
            'logFile' => '@app/runtime/logs/signError/' . date('Y-m-d') . '.log',
            'logVars' => ['_FILES'],
        ],
        [
            'class' => 'yii\log\FileTarget',
            'levels' => ['info'],
            'categories' => ['tmpMsg'],
            'logFile' => '@app/runtime/logs/tmpMsg/' . date('Y-m-d') . '.log',
            'logVars' => ['_FILES'],
        ],
        [
            'class' => 'yii\log\FileTarget',
            'levels' => ['info'],
            'categories' => ['trace'],
            'logFile' => '@app/runtime/logs/trace/' . date('Y-m-d') . '.log',
            'logVars' => ['_FILES'],
        ],
    ],
];