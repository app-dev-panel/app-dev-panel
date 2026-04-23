<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Stress fixture: realistic mix of cache operations producing genuine hits,
 * misses, overwrites, deletes, has-probes, batched writes, and a final flush().
 */
final class CacheHeavyAction
{
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
        Cache::flush();

        return new JsonResponse(['fixture' => 'cache:heavy', 'status' => 'ok']);
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
        Cache::putMany($products, 3600);
    }

    private function warmupUsers(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            Cache::put(
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
        Cache::many($keys);
    }

    private function readUsers(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            Cache::get(sprintf('user:%d', $i));
        }
    }

    private function readColdKeys(): void
    {
        Cache::get('missing:nobody-home');
        Cache::get('missing:stale-session');
        Cache::get('missing:invalidated');
        Cache::many(['missing:multi-1', 'missing:multi-2', 'missing:multi-3']);
    }

    private function probeHas(): void
    {
        Cache::has('product:1');
        Cache::has('product:5');
        Cache::has('user:1');
        Cache::has('user:7');
        Cache::has('ghost:a');
        Cache::has('ghost:b');
        Cache::has('ghost:c');
        Cache::has('ghost:d');
    }

    private function overwriteAndReread(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $key = sprintf('product:%d', $i);
            Cache::put(
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
            Cache::get($key);
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
        Cache::putMany($pages, 120);
        Cache::many(array_keys($pages));
    }

    private function deleteAndReread(): void
    {
        Cache::forget('user:6');
        Cache::forget('user:7');
        Cache::forget('user:8');
        Cache::get('user:6');
        Cache::get('user:7');
        Cache::get('user:8');
    }

    private function scalarValues(): void
    {
        Cache::put('config:site-name', 'ADP Playground', 900);
        Cache::get('config:site-name');

        Cache::put('counter:page-views', 42_195, 60);
        Cache::get('counter:page-views');

        Cache::put('rate:conversion', 1.3375, 300);
        Cache::get('rate:conversion');

        Cache::put('flag:feature-x-enabled', true, 1800);
        Cache::get('flag:feature-x-enabled');
    }

    private function longTailSingleOps(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $key = sprintf('report:monthly:%d', $i);
            if (Cache::get($key) === null) {
                Cache::put(
                    $key,
                    [
                        'month' => $i,
                        'totalRevenue' => 1000 * $i,
                        'orders' => 42 + $i,
                    ],
                    1800,
                );
            }
            Cache::get($key);
        }
    }
}
