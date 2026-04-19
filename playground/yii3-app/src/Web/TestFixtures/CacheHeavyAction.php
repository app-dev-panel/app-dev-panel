<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

/**
 * Stress fixture: runs 100 real PSR-16 operations through the decorated cache.
 */
final readonly class CacheHeavyAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private CacheInterface $cache,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $operations = ['set', 'get', 'get', 'get', 'delete', 'has'];

        for ($i = 0; $i < 100; $i++) {
            $operation = $operations[$i % count($operations)];
            $key = sprintf('app:item:%d', $i);

            switch ($operation) {
                case 'set':
                    $this->cache->set($key, [
                        'id' => $i,
                        'title' => sprintf('Item #%d', $i),
                        'tags' => ['tag-' . ($i % 5), 'tag-' . ($i % 7)],
                        'metadata' => ['created_at' => '2026-01-15T10:00:00Z', 'ttl' => 3600],
                    ]);
                    break;
                case 'get':
                    $this->cache->get($key);
                    break;
                case 'delete':
                    $this->cache->delete($key);
                    break;
                case 'has':
                    $this->cache->has($key);
                    break;
            }
        }

        return $this->responseFactory->createResponse(['fixture' => 'cache:heavy', 'status' => 'ok']);
    }
}
