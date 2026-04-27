<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CacheOperationRecord;

final class CacheHeavyAction
{
    public function __construct(
        private readonly CacheCollector $cache,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        for ($i = 0; $i < 120; $i++) {
            $hit = ($i % 3) !== 0;
            $this->cache->logCacheOperation(new CacheOperationRecord(
                pool: ($i % 2) === 0 ? 'default' : 'sessions',
                operation: 'get',
                key: "item.{$i}",
                hit: $hit,
                duration: 0.0001,
            ));
        }

        return ['fixture' => 'cache:heavy', 'status' => 'ok'];
    }
}
