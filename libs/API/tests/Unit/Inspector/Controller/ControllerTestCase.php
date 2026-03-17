<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use GuzzleHttp\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Base test case for controller tests.
 * Provides helpers for building requests, containers, and reading response data.
 */
abstract class ControllerTestCase extends TestCase
{
    private ?JsonResponseFactoryInterface $responseFactory = null;

    protected function createResponseFactory(): JsonResponseFactoryInterface
    {
        if ($this->responseFactory === null) {
            $factory = $this->createMock(JsonResponseFactoryInterface::class);
            $factory
                ->method('createJsonResponse')
                ->willReturnCallback(function (mixed $data, int $status = 200): ResponseInterface {
                    return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
                });
            $this->responseFactory = $factory;
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
     * Extract the data payload from a response.
     */
    protected function responseData(ResponseInterface $response): mixed
    {
        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}
