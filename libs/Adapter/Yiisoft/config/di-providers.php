<?php

declare(strict_types=1);

use AppDevPanel\Adapter\Yiisoft\DebugServiceProvider;

require_once __DIR__ . '/helpers.php';

if (!isAppDevPanelEnabled($params)) {
    return [];
}

return [
    'app-dev-panel/yiisoft/' . DebugServiceProvider::class => DebugServiceProvider::class,
];
