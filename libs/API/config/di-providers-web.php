<?php

declare(strict_types=1);

use AppDevPanel\Adapter\Yiisoft\Api\Debug\Provider\DebugApiProvider;

if (!(bool)($params['yiisoft/yii-debug-api']['enabled'] ?? false)) {
    return [];
}

return [
    'yiisoft/yii-debug-api' => DebugApiProvider::class,
];
