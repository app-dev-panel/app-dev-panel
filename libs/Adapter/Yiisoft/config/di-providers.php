<?php

declare(strict_types=1);

use AppDevPanel\Adapter\Yiisoft\DebugServiceProvider;

if (!(bool) ($params['app-dev-panel/yii-debug']['enabled'] ?? false)) {
    return [];
}

return [
    'app-dev-panel/yii-debug/' . DebugServiceProvider::class => DebugServiceProvider::class,
];
