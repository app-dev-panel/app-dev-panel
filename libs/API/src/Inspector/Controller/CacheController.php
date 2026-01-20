<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Controller;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\VarDumper\VarDumper;

class CacheController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {
    }

    public function view(
        ServerRequestInterface $request,
        ContainerInterface $container,
    ): ResponseInterface {
        $params = $request->getQueryParams();
        $key = $params['key'] ?? '';

        if ($key === '') {
            throw new RuntimeException('Cache key must not be empty.');
        }
        if (!$container->has(CacheInterface::class)) {
            // TODO: fix message
            throw new RuntimeException(
                'Psr\\SimpleCache\\CacheInterface does not exist in the application configuration.'
            );
        }
        $cache = $container->get(CacheInterface::class);

        if (!$cache->has($key)) {
            return $this->responseFactory->createResponse([
                'error' => 'Key does not exist in cache',
            ], 404);
        }

        $result = $cache->get($key);

        $response = VarDumper::create($result)->asPrimitives(255);

        return $this->responseFactory->createResponse($response);
    }

    public function delete(
        ServerRequestInterface $request,
        ContainerInterface $container,
    ): ResponseInterface {
        $params = $request->getQueryParams();
        $key = $params['key'] ?? '';

        if ($key === '') {
            throw new RuntimeException('Cache key must not be empty.');
        }
        if (!$container->has(CacheInterface::class)) {
            // TODO: fix message
            throw new RuntimeException(
                'Psr\\SimpleCache\\CacheInterface does not exist in the application configuration.'
            );
        }
        $cache = $container->get(CacheInterface::class);

        if (!$cache->has($key)) {
            throw new RuntimeException('Key does not exist in cache');
        }

        $result = $cache->delete($key);

        return $this->responseFactory->createResponse([
            'result' => $result,
        ]);
    }

    public function clear(
        ContainerInterface $container,
    ): ResponseInterface {
        if (!$container->has(CacheInterface::class)) {
            // TODO: fix message
            throw new RuntimeException(
                'Psr\\SimpleCache\\CacheInterface does not exist in the application configuration.'
            );
        }
        $cache = $container->get(CacheInterface::class);

        $result = $cache->clear();

        return $this->responseFactory->createResponse([
            'result' => $result,
        ]);
    }
}
