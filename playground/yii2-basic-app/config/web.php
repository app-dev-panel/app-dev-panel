<?php

declare(strict_types=1);

$params = require __DIR__ . '/params.php';

return [
    'id' => 'adp-yii2-playground',
    'name' => 'ADP Yii 2 Playground',
    'basePath' => dirname(__DIR__) . '/src',
    'runtimePath' => dirname(__DIR__) . '/runtime',
    'bootstrap' => ['debug-panel', 'log'],
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
                'GET /test/scenarios/logs' => 'test-scenarios/logs',
                'GET /test/scenarios/logs-context' => 'test-scenarios/logs-context',
                'GET /test/scenarios/events' => 'test-scenarios/events',
                'GET /test/scenarios/dump' => 'test-scenarios/dump',
                'GET /test/scenarios/timeline' => 'test-scenarios/timeline',
                'GET /test/scenarios/request-info' => 'test-scenarios/request-info',
                'GET /test/scenarios/exception' => 'test-scenarios/exception',
                'GET /test/scenarios/exception-chained' => 'test-scenarios/exception-chained',
                'GET /test/scenarios/multi' => 'test-scenarios/multi',
                'GET /test/scenarios/logs-heavy' => 'test-scenarios/logs-heavy',
                'GET /test/scenarios/http-client' => 'test-scenarios/http-client',
                'GET /test/scenarios/filesystem' => 'test-scenarios/filesystem',
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
