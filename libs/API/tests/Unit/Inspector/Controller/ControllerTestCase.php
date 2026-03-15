<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFactory;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Router\CurrentRoute;

/**
 * Base test case for controller tests.
 * Provides helpers for building requests, routes, containers, and reading response data.
 */
abstract class ControllerTestCase extends TestCase
{
    private ?DataResponseFactoryInterface $responseFactory = null;

    protected function createResponseFactory(): DataResponseFactoryInterface
    {
        if ($this->responseFactory === null) {
            $psr17 = new Psr17Factory();
            $this->responseFactory = new DataResponseFactory($psr17, $psr17);
        }
        return $this->responseFactory;
    }

    /**
     * Create a GET request with query parameters.
     */
    protected function get(array $queryParams = []): ServerRequest
    {
        $uri = '/test';
        if ($queryParams !== []) {
            $uri .= '?' . http_build_query($queryParams);
        }
        return new ServerRequest('GET', $uri);
    }

    /**
     * Create a POST/PUT request with a JSON body.
     */
    protected function jsonRequest(string $method, array $body): ServerRequest
    {
        $request = new ServerRequest($method, '/test');
        return $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Stream::create(json_encode($body, JSON_THROW_ON_ERROR)));
    }

    /**
     * Create a POST request with a JSON body.
     */
    protected function post(array $body): ServerRequest
    {
        return $this->jsonRequest('POST', $body);
    }

    /**
     * Create a PUT request with a JSON body.
     */
    protected function put(array $body): ServerRequest
    {
        return $this->jsonRequest('PUT', $body);
    }

    /**
     * Create a CurrentRoute with pre-set arguments (bypasses final class limitation).
     */
    protected function route(array $arguments): CurrentRoute
    {
        $currentRoute = new CurrentRoute();
        $property = new ReflectionProperty(CurrentRoute::class, 'arguments');
        $property->setValue($currentRoute, $arguments);
        return $currentRoute;
    }

    /**
     * Create a mock container that returns given services by class name.
     *
     * @param array<string, object> $services Map of class/id => instance
     */
    protected function container(array $services = []): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn(string $id) => isset($services[$id]));
        $container
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($services) {
                if (!isset($services[$id])) {
                    throw new \RuntimeException(sprintf('Service "%s" not found in test container.', $id));
                }
                return $services[$id];
            });
        return $container;
    }

    /**
     * Extract the data payload from a DataResponse.
     */
    protected function responseData(DataResponse $response): mixed
    {
        return $response->getData();
    }
}
