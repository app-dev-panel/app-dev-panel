<?php

declare(strict_types=1);

use Yiisoft\Definitions\ReferencesArray;
use AppDevPanel\Kernel\Debugger;

if (!(bool)($params['app-dev-panel/yii-debug']['enabled'] ?? false)) {
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
];
