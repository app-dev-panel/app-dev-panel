<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CacheOperationRecord;
use Illuminate\Http\JsonResponse;

final readonly class CacheHeavyAction
{
    public function __construct(
        private CacheCollector $cacheCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
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

            $this->cacheCollector->logCacheOperation(new CacheOperationRecord(
                pool: $pool,
                operation: $operation,
                key: $key,
                hit: $hit,
                duration: rand(100, 5_000) / 1_000_000,
                value: $value,
            ));
        }

        return new JsonResponse(['fixture' => 'cache:heavy', 'status' => 'ok']);
    }
}
