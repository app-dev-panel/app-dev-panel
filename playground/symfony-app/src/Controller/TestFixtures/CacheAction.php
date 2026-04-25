<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/cache', name: 'test_cache', methods: ['GET'])]
final readonly class CacheAction
{
    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {}

    public function __invoke(): JsonResponse
    {
        // Use Symfony's cache pool — the SymfonyCacheProxy intercepts these
        // calls and feeds cache operations to CacheCollector.

        // Set a value
        $item = $this->cache->getItem('user_42');
        $item->set(['id' => 42, 'name' => 'John Doe', 'email' => 'john@example.com']);
        $this->cache->save($item);

        // Get a hit
        $hitItem = $this->cache->getItem('user_42');

        // Get a miss
        $missItem = $this->cache->getItem('user_99');

        // Delete
        $this->cache->deleteItem('user_42');

        return new JsonResponse([
            'fixture' => 'cache:basic',
            'status' => 'ok',
            'hit' => $hitItem->isHit(),
            'miss' => !$missItem->isHit(),
        ]);
    }
}
