<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CacheOperationRecord;

final class CacheAction
{
    public function __construct(
        private readonly CacheCollector $cache,
    ) {}

    /**
     * @return array<string, string>
     */
    public function __invoke(): array
    {
        $store = [];

        // miss
        $this->cache->logCacheOperation(new CacheOperationRecord('default', 'get', 'user.42', false, 0.0005));
        // set
        $store['user.42'] = ['id' => 42, 'name' => 'Alice'];
        $this->cache->logCacheOperation(
            new CacheOperationRecord('default', 'set', 'user.42', false, 0.0003, $store['user.42']),
        );
        // hit
        $this->cache->logCacheOperation(
            new CacheOperationRecord('default', 'get', 'user.42', true, 0.0002, $store['user.42']),
        );

        return ['fixture' => 'cache:basic', 'status' => 'ok'];
    }
}
