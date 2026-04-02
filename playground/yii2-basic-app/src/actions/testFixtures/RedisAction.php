<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Collector\RedisCollector;
use AppDevPanel\Kernel\Collector\RedisCommandRecord;
use yii\base\Action;

final class RedisAction extends Action
{
    public function run(): array
    {
        /** @var \AppDevPanel\Adapter\Yii2\Module $module */
        $module = \Yii::$app->getModule('adp');

        /** @var RedisCollector|null $redisCollector */
        $redisCollector = $module->getCollector(RedisCollector::class);

        if ($redisCollector === null) {
            return ['fixture' => 'redis:basic', 'status' => 'error', 'message' => 'RedisCollector not found'];
        }

        // 1. SET a key
        $redisCollector->logCommand(new RedisCommandRecord(
            connection: 'default',
            command: 'SET',
            arguments: ['user:42', '{"name":"John Doe","email":"john@example.com"}'],
            result: true,
            duration: 0.0012,
        ));

        // 2. GET a key (hit)
        $redisCollector->logCommand(new RedisCommandRecord(
            connection: 'default',
            command: 'GET',
            arguments: ['user:42'],
            result: '{"name":"John Doe","email":"john@example.com"}',
            duration: 0.0005,
        ));

        // 3. DEL a key
        $redisCollector->logCommand(new RedisCommandRecord(
            connection: 'default',
            command: 'DEL',
            arguments: ['session:expired'],
            result: 1,
            duration: 0.0003,
        ));

        // 4. INCR a counter
        $redisCollector->logCommand(new RedisCommandRecord(
            connection: 'default',
            command: 'INCR',
            arguments: ['page:views'],
            result: 42,
            duration: 0.0002,
        ));

        // 5. LPUSH to a list on a different connection
        $redisCollector->logCommand(new RedisCommandRecord(
            connection: 'queue',
            command: 'LPUSH',
            arguments: ['jobs:pending', '{"type":"email","to":"john@example.com"}'],
            result: 3,
            duration: 0.0008,
        ));

        // 6. GET a key with error
        $redisCollector->logCommand(new RedisCommandRecord(
            connection: 'default',
            command: 'GET',
            arguments: ['broken:key'],
            result: null,
            duration: 0.015,
            error: 'WRONGTYPE Operation against a key holding the wrong kind of value',
        ));

        return ['fixture' => 'redis:basic', 'status' => 'ok'];
    }
}
