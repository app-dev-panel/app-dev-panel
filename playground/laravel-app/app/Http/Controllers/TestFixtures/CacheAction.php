<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CacheOperationRecord;
use Illuminate\Http\JsonResponse;

final readonly class CacheAction
{
    public function __construct(
        private CacheCollector $cacheCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->cacheCollector->logCacheOperation(new CacheOperationRecord(
            pool: 'default',
            operation: 'set',
            key: 'user:42',
            duration: 0.001,
            value: ['id' => 42, 'name' => 'John Doe', 'email' => 'john@example.com'],
        ));

        $this->cacheCollector->logCacheOperation(new CacheOperationRecord(
            pool: 'default',
            operation: 'get',
            key: 'user:42',
            hit: true,
            duration: 0.0005,
            value: ['id' => 42, 'name' => 'John Doe', 'email' => 'john@example.com'],
        ));

        $this->cacheCollector->logCacheOperation(new CacheOperationRecord(
            pool: 'default',
            operation: 'get',
            key: 'user:99',
            duration: 0.0003,
        ));

        $this->cacheCollector->logCacheOperation(new CacheOperationRecord(
            pool: 'default',
            operation: 'delete',
            key: 'user:42',
            duration: 0.0002,
        ));

        return new JsonResponse(['fixture' => 'cache:basic', 'status' => 'ok']);
    }
}
