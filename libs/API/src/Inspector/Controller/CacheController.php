<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Kernel\Inspector\Primitives;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

class CacheController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly ContainerInterface $container,
    ) {}

    public function view(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $key = $params['key'] ?? '';

        if ($key === '') {
            throw new RuntimeException('Cache key must not be empty.');
        }

        $cache = $this->getCache();

        if (!$cache->has($key)) {
            return $this->responseFactory->createJsonResponse([
                'error' => 'Key does not exist in cache',
            ], 404);
        }

        $result = $cache->get($key);
        $response = Primitives::dump($result, 255);

        return $this->responseFactory->createJsonResponse($response);
    }

    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $key = $params['key'] ?? '';

        if ($key === '') {
            throw new RuntimeException('Cache key must not be empty.');
        }

        $cache = $this->getCache();

        if (!$cache->has($key)) {
            throw new RuntimeException('Key does not exist in cache');
        }

        $result = $cache->delete($key);

        return $this->responseFactory->createJsonResponse([
            'result' => $result,
        ]);
    }

    public function clear(ServerRequestInterface $request): ResponseInterface
    {
        $cache = $this->getCache();
        $result = $cache->clear();

        return $this->responseFactory->createJsonResponse([
            'result' => $result,
        ]);
    }

    private function getCache(): CacheInterface
    {
        if (!$this->container->has(CacheInterface::class)) {
            throw new RuntimeException(sprintf(
                '"%s" is not available in the DI container. Make sure a PSR-16 cache implementation is configured.',
                CacheInterface::class,
            ));
        }
        return $this->container->get(CacheInterface::class);
    }
}
