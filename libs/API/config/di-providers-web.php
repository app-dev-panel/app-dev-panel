<?php

declare(strict_types=1);

use AppDevPanel\Api\Debug\Provider\DebugApiProvider;

if (!(bool)($params['app-dev-panel/yii-debug-api']['enabled'] ?? false)) {
    return [];
}

return [
    'app-dev-panel/yii-debug-api' => DebugApiProvider::class,
];
