<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

/**
 * Stress fixture: realistic mix of single/multi cache operations producing
 * genuine hits, misses, overwrites, deletes, has-probes, and a final clear().
 */
final readonly class CacheHeavyAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private CacheInterface $cache,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
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

        return $this->responseFactory->createResponse(['fixture' => 'cache:heavy', 'status' => 'ok']);
    }

    private function warmupProducts(): void
    {
        $products = [];
        for ($i = 1; $i <= 10; $i++) {
            $products[sprintf('product:%d', $i)] = [
                'id' => $i,
                'sku' => sprintf('SKU-%04d', $i),
                'name' => sprintf('Product #%d', $i),
                'price' => 10.0 + ($i * 2.5),
                'tags' => ['category-' . ($i % 3), 'tier-' . ($i % 4)],
                'stock' => ($i * 7) % 100,
            ];
        }
        $this->cache->setMultiple($products, 3600);
    }

    private function warmupUsers(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $this->cache->set(
                sprintf('user:%d', $i),
                [
                    'id' => $i,
                    'email' => sprintf('user%d@example.com', $i),
                    'roles' => $i === 1 ? ['admin', 'editor'] : ['viewer'],
                    'lastLogin' => '2026-04-23T09:' . sprintf('%02d', $i) . ':00Z',
                ],
                300,
            );
        }
    }

    private function readProducts(): void
    {
        $keys = [];
        for ($i = 1; $i <= 10; $i++) {
            $keys[] = sprintf('product:%d', $i);
        }
        $this->cache->getMultiple($keys);
    }

    private function readUsers(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $this->cache->get(sprintf('user:%d', $i));
        }
    }

    private function readColdKeys(): void
    {
        $this->cache->get('missing:nobody-home');
        $this->cache->get('missing:stale-session');
        $this->cache->get('missing:invalidated');
        $this->cache->getMultiple([
            'missing:multi-1',
            'missing:multi-2',
            'missing:multi-3',
        ]);
    }

    private function probeHas(): void
    {
        $this->cache->has('product:1');
        $this->cache->has('product:5');
        $this->cache->has('user:1');
        $this->cache->has('user:7');
        $this->cache->has('ghost:a');
        $this->cache->has('ghost:b');
        $this->cache->has('ghost:c');
        $this->cache->has('ghost:d');
    }

    private function overwriteAndReread(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $key = sprintf('product:%d', $i);
            $this->cache->set(
                $key,
                [
                    'id' => $i,
                    'sku' => sprintf('SKU-%04d', $i),
                    'name' => sprintf('Product #%d (discounted)', $i),
                    'price' => 9.99,
                    'tags' => ['category-' . ($i % 3), 'tier-' . ($i % 4), 'sale'],
                    'stock' => 0,
                ],
                600,
            );
            $this->cache->get($key);
        }
    }

    private function bulkPages(): void
    {
        $pages = [];
        for ($i = 1; $i <= 12; $i++) {
            $pages[sprintf('page:fragment:%d', $i)] = [
                'html' => sprintf('<section data-page="%d">…</section>', $i),
                'renderedAt' => '2026-04-23T10:' . sprintf('%02d', $i) . ':00Z',
                'ttl' => 120,
            ];
        }
        $this->cache->setMultiple($pages, 120);
        $this->cache->getMultiple(array_keys($pages));
    }

    private function deleteAndReread(): void
    {
        $this->cache->deleteMultiple(['user:6', 'user:7', 'user:8']);
        $this->cache->get('user:6');
        $this->cache->get('user:7');
        $this->cache->get('user:8');
    }

    private function scalarValues(): void
    {
        $this->cache->set('config:site-name', 'ADP Playground', 900);
        $this->cache->get('config:site-name');

        $this->cache->set('counter:page-views', 42_195, 60);
        $this->cache->get('counter:page-views');

        $this->cache->set('rate:conversion', 1.3375, 300);
        $this->cache->get('rate:conversion');

        $this->cache->set('flag:feature-x-enabled', true, 1800);
        $this->cache->get('flag:feature-x-enabled');
    }

    private function longTailSingleOps(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $key = sprintf('report:monthly:%d', $i);
            if ($this->cache->get($key) === null) {
                $this->cache->set(
                    $key,
                    [
                        'month' => $i,
                        'totalRevenue' => 1000 * $i,
                        'orders' => 42 + $i,
                    ],
                    1800,
                );
            }
            $this->cache->get($key);
        }
    }
}
