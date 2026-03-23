<?php

declare(strict_types=1);

use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIgnoreConfig;
use Yiisoft\Definitions\ReferencesArray;

require_once __DIR__ . '/helpers.php';

if (!isAppDevPanelEnabled($params)) {
    return [];
}

return [
    Debugger::class => [
        '__construct()' => [
            'collectors' => ReferencesArray::from(array_merge(
                $params['app-dev-panel/yiisoft']['collectors'],
                $params['app-dev-panel/yiisoft']['collectors.web'] ?? [],
            )),
            'ignoreConfig' => new DebuggerIgnoreConfig(
                requests: $params['app-dev-panel/yiisoft']['ignoredRequests'],
            ),
        ],
    ],
    WebAppInfoCollector::class => [
        '__construct()' => [
            'adapterName' => 'Yii3',
        ],
    ],
];
