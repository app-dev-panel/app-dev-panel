<?php

declare(strict_types=1);

use Yiisoft\Yii\Debug\DebugServiceProvider;

if (!(bool) ($params['yiisoft/yii-debug']['enabled'] ?? false)) {
    return [];
}

return [
    'yiisoft/yii-debug/' . DebugServiceProvider::class => DebugServiceProvider::class,
];
