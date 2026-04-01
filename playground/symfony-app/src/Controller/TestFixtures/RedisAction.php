<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\RedisCollector;
use AppDevPanel\Kernel\Collector\RedisCommandRecord;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/redis', name: 'test_redis', methods: ['GET'])]
final readonly class RedisAction
{
    public function __construct(
        private RedisCollector $redisCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        // 1. SET a key
        $this->redisCollector->logCommand(new RedisCommandRecord(
            connection: 'default',
            command: 'SET',
            arguments: ['user:42', '{"name":"John Doe","email":"john@example.com"}'],
            result: true,
            duration: 0.0012,
        ));

        // 2. GET a key (hit)
        $this->redisCollector->logCommand(new RedisCommandRecord(
            connection: 'default',
            command: 'GET',
            arguments: ['user:42'],
            result: '{"name":"John Doe","email":"john@example.com"}',
            duration: 0.0005,
        ));

        // 3. DEL a key
        $this->redisCollector->logCommand(new RedisCommandRecord(
            connection: 'default',
            command: 'DEL',
            arguments: ['session:expired'],
            result: 1,
            duration: 0.0003,
        ));

        // 4. INCR a counter
        $this->redisCollector->logCommand(new RedisCommandRecord(
            connection: 'default',
            command: 'INCR',
            arguments: ['page:views'],
            result: 42,
            duration: 0.0002,
        ));

        // 5. LPUSH to a list on a different connection
        $this->redisCollector->logCommand(new RedisCommandRecord(
            connection: 'queue',
            command: 'LPUSH',
            arguments: ['jobs:pending', '{"type":"email","to":"john@example.com"}'],
            result: 3,
            duration: 0.0008,
        ));

        // 6. GET a key with error
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
