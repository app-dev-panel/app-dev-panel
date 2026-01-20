<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Tests\Unit\Debug\Middleware;

use HttpSoft\Message\Response;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\StreamFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFactory;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\Debug\Api\Debug\Exception\NotFoundException;
use Yiisoft\Yii\Debug\Api\Debug\Middleware\ResponseDataWrapper;

final class ResponseDataWrapperTest extends TestCase
{
    public function testNotDataResponse(): void
    {
        $middleware = $this->createMiddleware();
        $response = $middleware->process(new ServerRequest(), $this->createRequestHandler(new Response(200)));

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testDataResponse(): void
    {
        $controllerRawResponse = ['id' => 1, 'name' => 'User name'];
        $factory = $this->createDataResponseFactory();
        $response = $factory->createResponse($controllerRawResponse);

        $middleware = $this->createMiddleware();
        $response = $middleware->process(new ServerRequest(), $this->createRequestHandler($response));

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(DataResponse::class, $response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([
            'id' => null,
            'data' => $controllerRawResponse,
            'error' => null,
            'success' => true,
        ], $response->getData());
    }

    public function testDataResponseErrorStatus(): void
    {
        $controllerRawResponse = ['id' => 1, 'name' => 'User name'];
        $factory = $this->createDataResponseFactory();
        $response = $factory->createResponse($controllerRawResponse, 400);

        $middleware = $this->createMiddleware();
        $response = $middleware->process(new ServerRequest(), $this->createRequestHandler($response));

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(DataResponse::class, $response);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals([
            'id' => null,
            'data' => $controllerRawResponse,
            'error' => null,
            'success' => false,
        ], $response->getData());
    }

    public function testDataResponseException(): void
    {
        $errorMessage = 'Test exception';
        $middleware = $this->createMiddleware();
        $response = $middleware->process(
            new ServerRequest(),
            $this->createExceptionRequestHandler(new NotFoundException($errorMessage))
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(DataResponse::class, $response);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals([
            'id' => null,
            'data' => null,
            'error' => $errorMessage,
            'success' => false,
        ], $response->getData());
    }

    private function createRequestHandler(ResponseInterface $response): RequestHandlerInterface
    {
        return new class ($response) implements RequestHandlerInterface {
            public function __construct(
                private ResponseInterface $response,
            ) {
            }

            public function handle($request): ResponseInterface
            {
                return $this->response;
            }
        };
    }

    private function createExceptionRequestHandler(Throwable $exception): RequestHandlerInterface
    {
        return new class ($exception) implements RequestHandlerInterface {
            public function __construct(
                private Throwable $exception,
            ) {
            }

            public function handle($request): ResponseInterface
            {
                throw $this->exception;
            }
        };
    }

    private function createMiddleware(): ResponseDataWrapper
    {
        $factory = $this->createDataResponseFactory();
        $currentRoute = new CurrentRoute();
        return new ResponseDataWrapper($factory, $currentRoute);
    }

    private function createDataResponseFactory(): DataResponseFactory
    {
        return new DataResponseFactory(
            new ResponseFactory(),
            new StreamFactory(),
        );
    }
}
