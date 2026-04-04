<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use AppDevPanel\Kernel\Collector\RedisCollector;
use AppDevPanel\Kernel\Collector\RedisCommandRecord;
use Illuminate\Http\JsonResponse;

/**
 * Redis fixture — calls the collector directly because there is no standard
 * Redis client interface to proxy. The RedisCollector is designed to be
 * fed by Redis client library plugins. This fixture simulates that data path.
 */
final class RedisAction
{
    public function __construct(
        private readonly RedisCollector $redisCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->redisCollector->logCommand(new RedisCommandRecord(
            connection: 'default',
            command: 'SET',
            arguments: ['user:42', '{"name":"John Doe","email":"john@example.com"}'],
            result: true,
            duration: 0.0012,
        ));

        $this->redisCollector->logCommand(new RedisCommandRecord(
            connection: 'default',
            command: 'GET',
            arguments: ['user:42'],
            result: '{"name":"John Doe","email":"john@example.com"}',
            duration: 0.0005,
        ));

        $this->redisCollector->logCommand(new RedisCommandRecord(
            connection: 'default',
            command: 'DEL',
            arguments: ['session:expired'],
            result: 1,
            duration: 0.0003,
        ));

        $this->redisCollector->logCommand(new RedisCommandRecord(
            connection: 'default',
            command: 'INCR',
            arguments: ['page:views'],
            result: 42,
            duration: 0.0002,
        ));

        $this->redisCollector->logCommand(new RedisCommandRecord(
            connection: 'queue',
            command: 'LPUSH',
            arguments: ['jobs:pending', '{"type":"email","to":"john@example.com"}'],
            result: 3,
            duration: 0.0008,
        ));

        $this->redisCollector->logCommand(new RedisCommandRecord(
            connection: 'default',
            command: 'GET',
            arguments: ['broken:key'],
            result: null,
            duration: 0.015,
            error: 'WRONGTYPE Operation against a key holding the wrong kind of value',
        ));

        return new JsonResponse(['fixture' => 'redis:basic', 'status' => 'ok']);
    }
}
