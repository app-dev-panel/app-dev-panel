<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CacheOperationRecord;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class CacheAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private CacheCollector $cacheCollector,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Simulate cache operations by calling the collector directly.
        // This tests the CacheCollector without requiring a real PSR-16 cache backend.

        // 1. SET a key with value
        $this->cacheCollector->logCacheOperation(new CacheOperationRecord(
            pool: 'default',
            operation: 'set',
            key: 'user:42',
            duration: 0.001,
            value: ['id' => 42, 'name' => 'John Doe', 'email' => 'john@example.com'],
        ));

        // 2. GET a key (cache hit) — returns value
        $this->cacheCollector->logCacheOperation(new CacheOperationRecord(
            pool: 'default',
            operation: 'get',
            key: 'user:42',
            hit: true,
            duration: 0.0005,
            value: ['id' => 42, 'name' => 'John Doe', 'email' => 'john@example.com'],
        ));

        // 3. GET a key (cache miss) — no value
        $this->cacheCollector->logCacheOperation(new CacheOperationRecord(
            pool: 'default',
            operation: 'get',
            key: 'user:99',
            duration: 0.0003,
        ));

        // 4. DELETE a key
        $this->cacheCollector->logCacheOperation(new CacheOperationRecord(
            pool: 'default',
            operation: 'delete',
            key: 'user:42',
            duration: 0.0002,
        ));

        return $this->responseFactory->createResponse(['fixture' => 'cache:basic', 'status' => 'ok']);
    }
}
