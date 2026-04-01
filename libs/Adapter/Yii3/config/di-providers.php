<?php

declare(strict_types=1);

use AppDevPanel\Adapter\Yii3\DebugServiceProvider;

require_once __DIR__ . '/helpers.php';

if (!isAppDevPanelEnabled($params)) {
    return [];
}

return [
    'app-dev-panel/yii3/' . DebugServiceProvider::class => DebugServiceProvider::class,
];
