<?php

declare(strict_types=1);

$params = require __DIR__ . '/params.php';

return [
    'id' => 'adp-yii2-playground',
    'name' => 'ADP Yii 2 Playground',
    'basePath' => dirname(__DIR__) . '/src',
    'runtimePath' => dirname(__DIR__) . '/runtime',
    'bootstrap' => ['debug-panel', 'log', \App\bootstrap\DatabaseBootstrap::class],
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
            'silentExitOnException' => true,
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'GET /' => 'site/index',
                'GET /api/users' => 'site/users',
                'GET /api/error' => 'site/error-demo',
                'GET /test/fixtures/logs' => 'test-fixtures/logs',
                'GET /test/fixtures/logs-context' => 'test-fixtures/logs-context',
                'GET /test/fixtures/events' => 'test-fixtures/events',
                'GET /test/fixtures/dump' => 'test-fixtures/dump',
                'GET /test/fixtures/timeline' => 'test-fixtures/timeline',
                'GET /test/fixtures/request-info' => 'test-fixtures/request-info',
                'GET /test/fixtures/exception' => 'test-fixtures/exception',
                'GET /test/fixtures/exception-chained' => 'test-fixtures/exception-chained',
                'GET /test/fixtures/multi' => 'test-fixtures/multi',
                'GET /test/fixtures/logs-heavy' => 'test-fixtures/logs-heavy',
                'GET /test/fixtures/http-client' => 'test-fixtures/http-client',
                'GET /test/fixtures/filesystem' => 'test-fixtures/filesystem',
                'GET /test/fixtures/filesystem-streams' => 'test-fixtures/filesystem-streams',
                'GET /test/fixtures/database' => 'test-fixtures/database',
                'GET /test/fixtures/mailer' => 'test-fixtures/mailer',
                'GET /test/fixtures/cache' => 'test-fixtures/cache',
                'GET /test/fixtures/cache-heavy' => 'test-fixtures/cache-heavy',
                'GET /test/fixtures/router' => 'test-fixtures/router',
                'GET /test/fixtures/validator' => 'test-fixtures/validator',
                'GET /test/fixtures/opentelemetry' => 'test-fixtures/opentelemetry',
                'GET /test/fixtures/messenger' => 'test-fixtures/messenger',
                'GET /test/fixtures/translator' => 'test-fixtures/translator',
                '/test/fixtures/reset' => 'test-fixtures/reset',
                '/test/fixtures/reset-cli' => 'test-fixtures/reset-cli',
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
                'mailer' => true,
                'assets' => true,
                'environment' => true,
                'cache' => true,
                'router' => true,
                'queue' => true,
                'validator' => true,
                'opentelemetry' => true,
            ],
            'ignoredRequests' => [
                '/debug/api/**',
                '/inspect/api/**',
                '/assets/**',
            ],
            'ignoredCommands' => [
                'help',
                'list',
                'cache/*',
            ],
        ],
    ],
];
