<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CacheOperationRecord;
use yii\base\Action;

final class CacheAction extends Action
{
    public function run(): array
    {
        /** @var \AppDevPanel\Adapter\Yii2\Module $module */
        $module = \Yii::$app->getModule('app-dev-panel');

        /** @var CacheCollector|null $cacheCollector */
        $cacheCollector = $module->getCollector(CacheCollector::class);

        if ($cacheCollector === null) {
            return ['fixture' => 'cache:basic', 'status' => 'error', 'message' => 'CacheCollector not found'];
        }

        $cacheCollector->logCacheOperation(new CacheOperationRecord(
            pool: 'default',
            operation: 'set',
            key: 'user:42',
            duration: 0.001,
            value: ['id' => 42, 'name' => 'John Doe', 'email' => 'john@example.com'],
        ));

        $cacheCollector->logCacheOperation(new CacheOperationRecord(
            pool: 'default',
            operation: 'get',
            key: 'user:42',
            hit: true,
            duration: 0.0005,
            value: ['id' => 42, 'name' => 'John Doe', 'email' => 'john@example.com'],
        ));

        $cacheCollector->logCacheOperation(new CacheOperationRecord(
            pool: 'default',
            operation: 'get',
            key: 'user:99',
            duration: 0.0003,
        ));

        $cacheCollector->logCacheOperation(new CacheOperationRecord(
            pool: 'default',
            operation: 'delete',
            key: 'user:42',
            duration: 0.0002,
        ));

        return ['fixture' => 'cache:basic', 'status' => 'ok'];
    }
}
