<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class RedisController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly ContainerInterface $container,
    ) {}

    public function ping(ServerRequestInterface $request): ResponseInterface
    {
        $redis = $this->getRedis();

        try {
            $result = $redis->ping();

            return $this->responseFactory->createJsonResponse([
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            return $this->responseFactory->createJsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function info(ServerRequestInterface $request): ResponseInterface
    {
        $redis = $this->getRedis();
        $params = $request->getQueryParams();
        $section = $params['section'] ?? null;

        try {
            $result = $section !== null && $section !== '' ? $redis->info($section) : $redis->info();

            return $this->responseFactory->createJsonResponse($result);
        } catch (\Throwable $e) {
            return $this->responseFactory->createJsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function dbSize(ServerRequestInterface $request): ResponseInterface
    {
        $redis = $this->getRedis();

        try {
            $result = $redis->dbSize();

            return $this->responseFactory->createJsonResponse([
                'size' => $result,
            ]);
        } catch (\Throwable $e) {
            return $this->responseFactory->createJsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function keys(ServerRequestInterface $request): ResponseInterface
    {
        $redis = $this->getRedis();
        $params = $request->getQueryParams();
        $pattern = $params['pattern'] ?? '*';
        $limit = min(max((int) ($params['limit'] ?? 100), 1), 1000);
        $cursor = (int) ($params['cursor'] ?? 0);

        try {
            /** @var array{0: int, 1: string[]}|false $result */
            $result = $redis->scan($cursor, ['match' => $pattern, 'count' => $limit]);

            if ($result === false) {
                return $this->responseFactory->createJsonResponse([
                    'keys' => [],
                    'cursor' => 0,
                ]);
            }

            return $this->responseFactory->createJsonResponse([
                'keys' => $result[1] ?? [],
                'cursor' => $result[0] ?? 0,
            ]);
        } catch (\Throwable $e) {
            return $this->responseFactory->createJsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function get(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $key = $params['key'] ?? '';

        if ($key === '') {
            throw new RuntimeException('Redis key must not be empty.');
        }

        $redis = $this->getRedis();

        try {
            $type = $redis->type($key);
            $ttl = $redis->ttl($key);
            $value = $this->getValueByType($redis, $key, $type);

            return $this->responseFactory->createJsonResponse([
                'key' => $key,
                'type' => $this->typeToString($type),
                'ttl' => $ttl,
                'value' => $value,
            ]);
        } catch (\Throwable $e) {
            return $this->responseFactory->createJsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $key = $params['key'] ?? '';

        if ($key === '') {
            throw new RuntimeException('Redis key must not be empty.');
        }

        $redis = $this->getRedis();

        try {
            $result = $redis->del($key);

            return $this->responseFactory->createJsonResponse([
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            return $this->responseFactory->createJsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function flushDb(ServerRequestInterface $request): ResponseInterface
    {
        $redis = $this->getRedis();

        try {
            $result = $redis->flushDB();

            return $this->responseFactory->createJsonResponse([
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            return $this->responseFactory->createJsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function getRedis(): \Redis
    {
        if (!$this->container->has(\Redis::class)) {
            throw new RuntimeException(sprintf(
                '"%s" is not available in the DI container. Make sure the phpredis extension is installed and a Redis instance is configured.',
                \Redis::class,
            ));
        }
        return $this->container->get(\Redis::class);
    }

    private function getValueByType(\Redis $redis, string $key, int $type): mixed
    {
        return match ($type) {
            \Redis::REDIS_STRING => $redis->get($key),
            \Redis::REDIS_LIST => $redis->lRange($key, 0, -1),
            \Redis::REDIS_SET => $redis->sMembers($key),
            \Redis::REDIS_ZSET => $redis->zRange($key, 0, -1, true),
            \Redis::REDIS_HASH => $redis->hGetAll($key),
            \Redis::REDIS_STREAM => $redis->xRange($key, '-', '+', 100),
            default => null,
        };
    }

    private function typeToString(int $type): string
    {
        return match ($type) {
            \Redis::REDIS_STRING => 'string',
            \Redis::REDIS_LIST => 'list',
            \Redis::REDIS_SET => 'set',
            \Redis::REDIS_ZSET => 'zset',
            \Redis::REDIS_HASH => 'hash',
            \Redis::REDIS_STREAM => 'stream',
            \Redis::REDIS_NOT_FOUND => 'none',
            default => 'unknown',
        };
    }
}
