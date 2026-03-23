<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Debug\Middleware;

use AppDevPanel\Api\Debug\Exception\NotFoundException;
use AppDevPanel\Api\Debug\Middleware\ResponseDataWrapper;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class ResponseDataWrapperTest extends TestCase
{
    public function testNonJsonResponsePassesThrough(): void
    {
        $middleware = $this->createMiddleware();
        $response = new Response(200, ['Content-Type' => 'text/event-stream'], 'data: test');
        $result = $middleware->process(new ServerRequest('GET', '/test'), $this->createRequestHandler($response));

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame('text/event-stream', $result->getHeaderLine('Content-Type'));
    }

    public function testJsonResponse(): void
    {
        $controllerRawResponse = ['id' => 1, 'name' => 'User name'];
        $innerResponse = new Response(200, ['Content-Type' => 'application/json'], json_encode($controllerRawResponse));

        $middleware = $this->createMiddleware();
        $response = $middleware->process(
            new ServerRequest('GET', '/test'),
            $this->createRequestHandler($innerResponse),
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(
            [
                'id' => null,
                'data' => $controllerRawResponse,
                'error' => null,
                'success' => true,
            ],
            $data,
        );
    }

    public function testJsonResponseErrorStatus(): void
    {
        $controllerRawResponse = ['id' => 1, 'name' => 'User name'];
        $innerResponse = new Response(400, ['Content-Type' => 'application/json'], json_encode($controllerRawResponse));

        $middleware = $this->createMiddleware();
        $response = $middleware->process(
            new ServerRequest('GET', '/test'),
            $this->createRequestHandler($innerResponse),
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(
            [
                'id' => null,
                'data' => $controllerRawResponse,
                'error' => null,
                'success' => false,
            ],
            $data,
        );
    }

    public function testDataResponseException(): void
    {
        $errorMessage = 'Test exception';
        $middleware = $this->createMiddleware();
        $response = $middleware->process(
            new ServerRequest('GET', '/test'),
            $this->createExceptionRequestHandler(new NotFoundException($errorMessage)),
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(404, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(
            [
                'id' => null,
                'data' => null,
                'error' => $errorMessage,
                'success' => false,
            ],
            $data,
        );
    }

    private function createRequestHandler(ResponseInterface $response): RequestHandlerInterface
    {
        return new class($response) implements RequestHandlerInterface {
            public function __construct(
                private ResponseInterface $response,
            ) {}

            public function handle($request): ResponseInterface
            {
                return $this->response;
            }
        };
    }

    private function createExceptionRequestHandler(Throwable $exception): RequestHandlerInterface
    {
        return new class($exception) implements RequestHandlerInterface {
            public function __construct(
                private Throwable $exception,
            ) {}

            public function handle($request): ResponseInterface
            {
                throw $this->exception;
            }
        };
    }

    private function createMiddleware(): ResponseDataWrapper
    {
        return new ResponseDataWrapper($this->createJsonResponseFactory());
    }

    private function createJsonResponseFactory(): JsonResponseFactoryInterface
    {
        $factory = $this->createMock(JsonResponseFactoryInterface::class);
        $factory
            ->method('createJsonResponse')
            ->willReturnCallback(static function (mixed $data, int $status = 200): Response {
                return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
            });
        return $factory;
    }
}
