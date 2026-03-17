<?php

declare(strict_types=1);

$params = require __DIR__ . '/params.php';

return [
    'id' => 'adp-yii2-playground',
    'name' => 'ADP Yii 2 Playground',
    'basePath' => dirname(__DIR__) . '/src',
    'runtimePath' => dirname(__DIR__) . '/runtime',
    'bootstrap' => ['log'],
    'controllerNamespace' => 'App\\controllers',
    'params' => $params,

    'components' => [
        'request' => [
            'cookieValidationKey' => 'adp-yii2-playground-secret-key',
        ],
        'response' => [
            'class' => \yii\web\Response::class,
            'format' => \yii\web\Response::FORMAT_JSON,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'GET /' => 'site/index',
                'GET /api/users' => 'site/users',
                'GET /api/error' => 'site/error-demo',
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'sqlite:' . dirname(__DIR__) . '/runtime/db.sqlite',
            'charset' => 'utf8',
        ],
    ],

    'modules' => [
        'debug-panel' => [
            'class' => \AppDevPanel\Adapter\Yii2\Module::class,
            'storagePath' => '@runtime/debug',
            'historySize' => 50,
            'collectors' => [
                'request' => true,
                'exception' => true,
                'log' => true,
                'event' => true,
                'service' => true,
                'http_client' => true,
                'timeline' => true,
                'var_dumper' => true,
                'filesystem_stream' => true,
                'http_stream' => true,
                'command' => true,
                'db' => true,
                'yii_log' => true,
            ],
            'ignoredRequests' => [
                '/debug/api/*',
                '/inspect/api/*',
                '/assets/*',
            ],
            'ignoredCommands' => [
                'help',
                'list',
                'cache/*',
            ],
        ],
    ],
];
