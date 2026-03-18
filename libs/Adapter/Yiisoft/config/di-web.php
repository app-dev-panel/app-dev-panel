<?php

declare(strict_types=1);

use Yiisoft\Definitions\ReferencesArray;
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
                    $params['app-dev-panel/yiisoft']['collectors'],
                    $params['app-dev-panel/yiisoft']['collectors.web'] ?? [],
                )
            ),
            'ignoredRequests' => $params['app-dev-panel/yiisoft']['ignoredRequests'],
        ],
    ],
];
