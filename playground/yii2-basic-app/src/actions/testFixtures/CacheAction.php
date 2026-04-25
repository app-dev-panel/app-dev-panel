<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

/**
 * Exercises the real `Yii::$app->cache` component so `CacheProxy` (registered by
 * the Yii 2 adapter) captures the operations into `CacheCollector`.
 *
 * No direct collector calls — the proxy does the recording.
 */
final class CacheAction extends Action
{
    public function run(): array
    {
        $cache = \Yii::$app->cache ?? null;

        if ($cache === null) {
            return ['fixture' => 'cache:basic', 'status' => 'error', 'message' => 'cache component not configured'];
        }

        $userData = ['id' => 42, 'name' => 'John Doe', 'email' => 'john@example.com'];

        // Store and fetch the same key to demonstrate a hit.
        $cache->set('user:42', $userData);
        $cache->get('user:42');

        // Fetch a missing key — proxy records it as a miss.
        $cache->get('user:99');

        // exists() demonstrates the `exists` operation.
        $cache->exists('user:42');

        // delete() shows the `delete` operation; subsequent get is a miss.
        $cache->delete('user:42');
        $cache->get('user:42');

        return ['fixture' => 'cache:basic', 'status' => 'ok'];
    }
}
