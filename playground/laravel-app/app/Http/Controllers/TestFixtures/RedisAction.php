<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;

/**
 * Requires Redis to be configured (phpredis or predis).
 * The RedisListener captures CommandExecuted events automatically.
 */
final class RedisAction
{
    public function __invoke(): JsonResponse
    {
        $redis = Redis::connection();

        // Enable event tracking so CommandExecuted events are fired
        $redis->enableEvents();

        $redis->set('user:42', json_encode(['name' => 'John Doe', 'email' => 'john@example.com']));
        $redis->get('user:42');
        $redis->del('session:expired');
        $redis->incr('page:views');
        $redis->lpush('jobs:pending', json_encode(['type' => 'email', 'to' => 'john@example.com']));
        $redis->get('user:99');

        return new JsonResponse(['fixture' => 'redis:basic', 'status' => 'ok']);
    }
}
