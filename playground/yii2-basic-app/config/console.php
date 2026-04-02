<?php

declare(strict_types=1);

$params = require __DIR__ . '/params.php';

return [
    'id' => 'adp-yii2-playground-console',
    'basePath' => dirname(__DIR__) . '/src',
    'runtimePath' => dirname(__DIR__) . '/runtime',
    'bootstrap' => ['adp', 'log'],
    'controllerNamespace' => 'App\\commands',
    'params' => $params,

    'components' => [
        'log' => [
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
        'adp' => [
            'class' => \AppDevPanel\Adapter\Yii2\Module::class,
            'storagePath' => '@runtime/debug',
            'historySize' => 50,
        ],
    ],
];
