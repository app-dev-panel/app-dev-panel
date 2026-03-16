<?php

declare(strict_types=1);

use Yiisoft\Definitions\ReferencesArray;
use AppDevPanel\Cli\Command\DebugServerCommand;
use AppDevPanel\Kernel\Debugger;

require_once __DIR__ . '/helpers.php';

if (!isAppDevPanelEnabled($params)) {
    return [];
}

return [
    Debugger::class => [
        '__construct()' => [
            'collectors' => ReferencesArray::from(
                array_merge(
                    $params['app-dev-panel/yii-debug']['collectors'],
                    $params['app-dev-panel/yii-debug']['collectors.console'] ?? []
                )
            ),
            'ignoredCommands' => $params['app-dev-panel/yii-debug']['ignoredCommands'],
        ],
    ],
    DebugServerCommand::class => [
        '__construct()' => [
            'address' => $params['app-dev-panel/yii-debug']['devServer']['address'],
            'port' => $params['app-dev-panel/yii-debug']['devServer']['port'],
        ],
    ],
];
