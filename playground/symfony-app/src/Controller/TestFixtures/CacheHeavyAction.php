<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/cache-heavy', name: 'test_cache_heavy', methods: ['GET'])]
final readonly class CacheHeavyAction
{
    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {}

    public function __invoke(): JsonResponse
    {
        // Generate many cache operations — the SymfonyCacheProxy intercepts all
        // calls and feeds them to CacheCollector.
        for ($i = 0; $i < 100; $i++) {
            $key = sprintf('app_item_%d', $i);

            if (($i % 6) === 0) {
                // Set
                $item = $this->cache->getItem($key);
                $item->set([
                    'id' => $i,
                    'title' => sprintf('Item #%d', $i),
                    'tags' => ['tag-' . ($i % 5), 'tag-' . ($i % 7)],
                    'metadata' => ['created_at' => '2026-01-15T10:00:00Z', 'ttl' => 3600],
                ]);
                $this->cache->save($item);
            } elseif (($i % 6) === 4) {
                // Delete
                $this->cache->deleteItem($key);
            } elseif (($i % 6) === 5) {
                // Has
                $this->cache->hasItem($key);
            } else {
                // Get (may be hit or miss depending on whether item was set before)
                $this->cache->getItem($key);
            }
        }

        return new JsonResponse(['fixture' => 'cache:heavy', 'status' => 'ok']);
    }
}
