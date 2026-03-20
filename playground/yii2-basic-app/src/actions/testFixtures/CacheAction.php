<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Collector\CacheCollector;
use yii\base\Action;

final class CacheAction extends Action
{
    public function run(): array
    {
        /** @var \AppDevPanel\Adapter\Yii2\Module $module */
        $module = \Yii::$app->getModule('debug-panel');

        /** @var CacheCollector|null $cacheCollector */
        $cacheCollector = $module->getCollector(CacheCollector::class);

        if ($cacheCollector === null) {
            return ['fixture' => 'cache:basic', 'status' => 'error', 'message' => 'CacheCollector not found'];
        }

        // Simulate cache operations by calling the collector directly.
        // This tests the CacheCollector without requiring a real PSR-16 cache backend.

        // 1. SET a key
        $cacheCollector->logCacheOperation(
            pool: 'default',
            operation: 'set',
            key: 'user:42',
            hit: false,
            duration: 0.001,
        );

        // 2. GET a key (cache hit)
        $cacheCollector->logCacheOperation(
            pool: 'default',
            operation: 'get',
            key: 'user:42',
            hit: true,
            duration: 0.0005,
        );

        // 3. GET a key (cache miss)
        $cacheCollector->logCacheOperation(
            pool: 'default',
            operation: 'get',
            key: 'user:99',
            hit: false,
            duration: 0.0003,
        );

        // 4. DELETE a key
        $cacheCollector->logCacheOperation(
            pool: 'default',
            operation: 'delete',
            key: 'user:42',
            hit: false,
            duration: 0.0002,
        );

        return ['fixture' => 'cache:basic', 'status' => 'ok'];
    }
}
