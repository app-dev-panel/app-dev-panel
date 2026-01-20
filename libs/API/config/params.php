<?php

declare(strict_types=1);

use Codeception\Extension;
use AppDevPanel\Adapter\Yiisoft\Api\Debug\Middleware\DebugHeaders;
use AppDevPanel\Adapter\Yiisoft\Api\Inspector\Command\CodeceptionCommand;
use AppDevPanel\Adapter\Yiisoft\Api\Inspector\Command\PHPUnitCommand;
use AppDevPanel\Adapter\Yiisoft\Api\Inspector\Command\PsalmCommand;

$testCommands = [];
if (class_exists(\PHPUnit\Framework\Test::class)) {
    $testCommands[PHPUnitCommand::COMMAND_NAME] = PHPUnitCommand::class;
}
if (class_exists(Extension::class)) {
    $testCommands[CodeceptionCommand::COMMAND_NAME] = CodeceptionCommand::class;
}

return [
    'yiisoft/yii-debug' => [
        'ignoredRequests' => [
            '/debug**',
            '/inspect**',
        ],
    ],
    'yiisoft/yii-debug-api' => [
        'enabled' => true,
        'allowedIPs' => ['127.0.0.1', '::1'],
        'allowedHosts' => [],
        'middlewares' => [
            DebugHeaders::class,
        ],
        'inspector' => [
            'commandMap' => [
                'tests' => $testCommands,
                'analyse' => [
                    PsalmCommand::COMMAND_NAME => PsalmCommand::class,
                ],
            ],
        ],
    ],
    'yiisoft/yii-swagger' => [
        'annotation-paths' => [
            dirname(__DIR__) . '/src/Debug/Controller',
            dirname(__DIR__) . '/src/Debug/Middleware',
        ],
    ],
];
