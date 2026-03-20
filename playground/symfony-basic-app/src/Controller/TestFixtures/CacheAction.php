<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\CacheCollector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/cache', name: 'test_cache', methods: ['GET'])]
final readonly class CacheAction
{
    public function __construct(
        private CacheCollector $cacheCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        // Simulate cache operations by calling the collector directly.
        // This tests the CacheCollector without requiring a real PSR-16 cache backend.

        // 1. SET a key
        $this->cacheCollector->logCacheOperation(
            pool: 'default',
            operation: 'set',
            key: 'user:42',
            hit: false,
            duration: 0.001,
        );

        // 2. GET a key (cache hit)
        $this->cacheCollector->logCacheOperation(
            pool: 'default',
            operation: 'get',
            key: 'user:42',
            hit: true,
            duration: 0.0005,
        );

        // 3. GET a key (cache miss)
        $this->cacheCollector->logCacheOperation(
            pool: 'default',
            operation: 'get',
            key: 'user:99',
            hit: false,
            duration: 0.0003,
        );

        // 4. DELETE a key
        $this->cacheCollector->logCacheOperation(
            pool: 'default',
            operation: 'delete',
            key: 'user:42',
            hit: false,
            duration: 0.0002,
        );

        return new JsonResponse(['fixture' => 'cache:basic', 'status' => 'ok']);
    }
}
