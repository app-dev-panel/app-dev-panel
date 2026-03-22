<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Collector\CacheCollector;
use yii\base\Action;

final class CacheHeavyAction extends Action
{
    public function run(): array
    {
        /** @var \AppDevPanel\Adapter\Yii2\Module $module */
        $module = \Yii::$app->getModule('debug-panel');

        /** @var CacheCollector|null $cacheCollector */
        $cacheCollector = $module->getCollector(CacheCollector::class);

        if ($cacheCollector === null) {
            return ['fixture' => 'cache:heavy', 'status' => 'error', 'message' => 'CacheCollector not found'];
        }

        $pools = ['default', 'sessions', 'metadata'];
        $operations = ['set', 'get', 'get', 'get', 'delete', 'has'];

        for ($i = 0; $i < 100; $i++) {
            $pool = $pools[$i % count($pools)];
            $operation = $operations[$i % count($operations)];
            $key = sprintf('app:%s:item:%d', $pool, $i);
            $hit = $operation === 'get' && ($i % 3) !== 0;

            $value = null;
            if ($operation === 'set') {
                $value = [
                    'id' => $i,
                    'title' => sprintf('Item #%d', $i),
                    'tags' => ['tag-' . ($i % 5), 'tag-' . ($i % 7)],
                    'metadata' => ['created_at' => '2026-01-15T10:00:00Z', 'ttl' => 3600],
                ];
            } elseif ($operation === 'get' && $hit) {
                $value = [
                    'id' => $i,
                    'title' => sprintf('Item #%d', $i),
                    'cached' => true,
                ];
            }

            $cacheCollector->logCacheOperation(
                pool: $pool,
                operation: $operation,
                key: $key,
                hit: $hit,
                duration: rand(100, 5_000) / 1_000_000,
                value: $value,
            );
        }

        return ['fixture' => 'cache:heavy', 'status' => 'ok'];
    }
}
