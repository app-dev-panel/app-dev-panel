<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Stress fixture: realistic mix of PSR-6 operations producing genuine hits,
 * misses, overwrites, deletes, has-probes, batched writes, and a final clear().
 */
#[Route('/test/fixtures/cache-heavy', name: 'test_cache_heavy', methods: ['GET'])]
final readonly class CacheHeavyAction
{
    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->warmupProducts();
        $this->warmupUsers();
        $this->readProducts();
        $this->readUsers();
        $this->readColdKeys();
        $this->probeHas();
        $this->overwriteAndReread();
        $this->bulkPages();
        $this->deleteAndReread();
        $this->scalarValues();
        $this->longTailSingleOps();
        $this->cache->clear();

        return new JsonResponse(['fixture' => 'cache:heavy', 'status' => 'ok']);
    }

    private function warmupProducts(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $item = $this->cache->getItem(sprintf('product.%d', $i));
            $item->set([
                'id' => $i,
                'sku' => sprintf('SKU-%04d', $i),
                'name' => sprintf('Product #%d', $i),
                'price' => 10.0 + ($i * 2.5),
                'tags' => ['category-' . ($i % 3), 'tier-' . ($i % 4)],
                'stock' => ($i * 7) % 100,
            ]);
            $item->expiresAfter(3600);
            $this->cache->saveDeferred($item);
        }
        $this->cache->commit();
    }

    private function warmupUsers(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $item = $this->cache->getItem(sprintf('user.%d', $i));
            $item->set([
                'id' => $i,
                'email' => sprintf('user%d@example.com', $i),
                'roles' => $i === 1 ? ['admin', 'editor'] : ['viewer'],
                'lastLogin' => '2026-04-23T09:' . sprintf('%02d', $i) . ':00Z',
            ]);
            $item->expiresAfter(300);
            $this->cache->save($item);
        }
    }

    private function readProducts(): void
    {
        $keys = [];
        for ($i = 1; $i <= 10; $i++) {
            $keys[] = sprintf('product.%d', $i);
        }
        foreach ($this->cache->getItems($keys) as $item) {
            $item->isHit();
        }
    }

    private function readUsers(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $this->cache->getItem(sprintf('user.%d', $i));
        }
    }

    private function readColdKeys(): void
    {
        $this->cache->getItem('missing.nobody-home');
        $this->cache->getItem('missing.stale-session');
        $this->cache->getItem('missing.invalidated');
        foreach ($this->cache->getItems(['missing.multi-1', 'missing.multi-2', 'missing.multi-3']) as $item) {
            $item->isHit();
        }
    }

    private function probeHas(): void
    {
        $this->cache->hasItem('product.1');
        $this->cache->hasItem('product.5');
        $this->cache->hasItem('user.1');
        $this->cache->hasItem('user.7');
        $this->cache->hasItem('ghost.a');
        $this->cache->hasItem('ghost.b');
        $this->cache->hasItem('ghost.c');
        $this->cache->hasItem('ghost.d');
    }

    private function overwriteAndReread(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $key = sprintf('product.%d', $i);
            $item = $this->cache->getItem($key);
            $item->set([
                'id' => $i,
                'sku' => sprintf('SKU-%04d', $i),
                'name' => sprintf('Product #%d (discounted)', $i),
                'price' => 9.99,
                'tags' => ['category-' . ($i % 3), 'tier-' . ($i % 4), 'sale'],
                'stock' => 0,
            ]);
            $item->expiresAfter(600);
            $this->cache->save($item);
            $this->cache->getItem($key);
        }
    }

    private function bulkPages(): void
    {
        $keys = [];
        for ($i = 1; $i <= 12; $i++) {
            $key = sprintf('page.fragment.%d', $i);
            $keys[] = $key;
            $item = $this->cache->getItem($key);
            $item->set([
                'html' => sprintf('<section data-page="%d">…</section>', $i),
                'renderedAt' => '2026-04-23T10:' . sprintf('%02d', $i) . ':00Z',
                'ttl' => 120,
            ]);
            $item->expiresAfter(120);
            $this->cache->saveDeferred($item);
        }
        $this->cache->commit();
        foreach ($this->cache->getItems($keys) as $item) {
            $item->isHit();
        }
    }

    private function deleteAndReread(): void
    {
        $this->cache->deleteItems(['user.6', 'user.7', 'user.8']);
        $this->cache->getItem('user.6');
        $this->cache->getItem('user.7');
        $this->cache->getItem('user.8');
    }

    private function scalarValues(): void
    {
        $this->saveScalar('config.site-name', 'ADP Playground', 900);
        $this->cache->getItem('config.site-name');

        $this->saveScalar('counter.page-views', 42_195, 60);
        $this->cache->getItem('counter.page-views');

        $this->saveScalar('rate.conversion', 1.3375, 300);
        $this->cache->getItem('rate.conversion');

        $this->saveScalar('flag.feature-x-enabled', true, 1800);
        $this->cache->getItem('flag.feature-x-enabled');
    }

    private function longTailSingleOps(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $key = sprintf('report.monthly.%d', $i);
            $item = $this->cache->getItem($key);
            if (!$item->isHit()) {
                $item->set([
                    'month' => $i,
                    'totalRevenue' => 1000 * $i,
                    'orders' => 42 + $i,
                ]);
                $item->expiresAfter(1800);
                $this->cache->save($item);
            }
            $this->cache->getItem($key);
        }
    }

    private function saveScalar(string $key, mixed $value, int $ttl): void
    {
        $item = $this->cache->getItem($key);
        assert($item instanceof CacheItemInterface);
        $item->set($value);
        $item->expiresAfter($ttl);
        $this->cache->save($item);
    }
}
