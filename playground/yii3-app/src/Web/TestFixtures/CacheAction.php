<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

/**
 * Exercises the PSR-16 cache. The injected CacheInterface is transparently
 * decorated by Psr16CacheProxy (Yii3 adapter) — every operation feeds the
 * CacheCollector without touching collector APIs here.
 */
final readonly class CacheAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private CacheInterface $cache,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $payload = ['id' => 42, 'name' => 'John Doe', 'email' => 'john@example.com'];

        // 1. SET a key with value.
        $this->cache->set('user:42', $payload);

        // 2. GET an existing key (cache hit).
        $this->cache->get('user:42');

        // 3. GET a missing key (cache miss).
        $this->cache->get('user:99');

        // 4. HAS check.
        $this->cache->has('user:42');

        // 5. DELETE the key.
        $this->cache->delete('user:42');

        return $this->responseFactory->createResponse(['fixture' => 'cache:basic', 'status' => 'ok']);
    }
}
