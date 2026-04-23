<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;
use yii\caching\CacheInterface;

/**
 * Stress fixture: realistic mix of Yii 2 cache operations producing genuine
 * hits, misses, overwrites, deletes, exists-probes, multi-ops, and a final flush().
 */
final class CacheHeavyAction extends Action
{
    public function run(): array
    {
        $cache = \Yii::$app->cache;
        $cache->flush();

        $this->warmupProducts($cache);
        $this->warmupUsers($cache);
        $this->readProducts($cache);
        $this->readUsers($cache);
        $this->readColdKeys($cache);
        $this->probeExists($cache);
        $this->overwriteAndReread($cache);
        $this->bulkPages($cache);
        $this->deleteAndReread($cache);
        $this->scalarValues($cache);
        $this->longTailSingleOps($cache);
        $cache->flush();

        return ['fixture' => 'cache:heavy', 'status' => 'ok'];
    }

    private function warmupProducts(CacheInterface $cache): void
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
        $cache->multiSet($products, 3600);
    }

    private function warmupUsers(CacheInterface $cache): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $cache->set(
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

    private function readProducts(CacheInterface $cache): void
    {
        $keys = [];
        for ($i = 1; $i <= 10; $i++) {
            $keys[] = sprintf('product:%d', $i);
        }
        $cache->multiGet($keys);
    }

    private function readUsers(CacheInterface $cache): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $cache->get(sprintf('user:%d', $i));
        }
    }

    private function readColdKeys(CacheInterface $cache): void
    {
        $cache->get('missing:nobody-home');
        $cache->get('missing:stale-session');
        $cache->get('missing:invalidated');
        $cache->multiGet(['missing:multi-1', 'missing:multi-2', 'missing:multi-3']);
    }

    private function probeExists(CacheInterface $cache): void
    {
        $cache->exists('product:1');
        $cache->exists('product:5');
        $cache->exists('user:1');
        $cache->exists('user:7');
        $cache->exists('ghost:a');
        $cache->exists('ghost:b');
        $cache->exists('ghost:c');
        $cache->exists('ghost:d');
    }

    private function overwriteAndReread(CacheInterface $cache): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $key = sprintf('product:%d', $i);
            $cache->set(
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
            $cache->get($key);
        }
    }

    private function bulkPages(CacheInterface $cache): void
    {
        $pages = [];
        for ($i = 1; $i <= 12; $i++) {
            $pages[sprintf('page:fragment:%d', $i)] = [
                'html' => sprintf('<section data-page="%d">…</section>', $i),
                'renderedAt' => '2026-04-23T10:' . sprintf('%02d', $i) . ':00Z',
                'ttl' => 120,
            ];
        }
        $cache->multiSet($pages, 120);
        $cache->multiGet(array_keys($pages));
    }

    private function deleteAndReread(CacheInterface $cache): void
    {
        $cache->delete('user:6');
        $cache->delete('user:7');
        $cache->delete('user:8');
        $cache->get('user:6');
        $cache->get('user:7');
        $cache->get('user:8');
    }

    private function scalarValues(CacheInterface $cache): void
    {
        $cache->set('config:site-name', 'ADP Playground', 900);
        $cache->get('config:site-name');

        $cache->set('counter:page-views', 42_195, 60);
        $cache->get('counter:page-views');

        $cache->set('rate:conversion', 1.3375, 300);
        $cache->get('rate:conversion');

        $cache->set('flag:feature-x-enabled', true, 1800);
        $cache->get('flag:feature-x-enabled');
    }

    private function longTailSingleOps(CacheInterface $cache): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $key = sprintf('report:monthly:%d', $i);
            if ($cache->get($key) === false) {
                $cache->set(
                    $key,
                    [
                        'month' => $i,
                        'totalRevenue' => 1000 * $i,
                        'orders' => 42 + $i,
                    ],
                    1800,
                );
            }
            $cache->get($key);
        }
    }
}
