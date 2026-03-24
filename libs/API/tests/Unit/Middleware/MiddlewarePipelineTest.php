<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Middleware;

use AppDevPanel\Api\Middleware\MiddlewarePipeline;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewarePipelineTest extends TestCase
{
    public function testFallbackHandlerCalledWithNoMiddleware(): void
    {
        $fallback = $this->createHandler(new Response(200, [], 'fallback'));
        $pipeline = new MiddlewarePipeline($fallback);

        $response = $pipeline->handle(new ServerRequest('GET', '/test'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('fallback', (string) $response->getBody());
    }

    public function testSingleMiddlewareExecutes(): void
    {
        $fallback = $this->createHandler(new Response(200, [], 'original'));
        $pipeline = new MiddlewarePipeline($fallback);

        $pipeline->pipe($this->createHeaderMiddleware('X-Test', 'added'));

        $response = $pipeline->handle(new ServerRequest('GET', '/test'));

        $this->assertSame('added', $response->getHeaderLine('X-Test'));
        $this->assertSame('original', (string) $response->getBody());
    }

    public function testMiddlewareExecutesInOrder(): void
    {
        $order = [];
        $fallback = $this->createHandler(new Response(200));
        $pipeline = new MiddlewarePipeline($fallback);

        $pipeline->pipe($this->createOrderTrackingMiddleware($order, 'first'));
        $pipeline->pipe($this->createOrderTrackingMiddleware($order, 'second'));
        $pipeline->pipe($this->createOrderTrackingMiddleware($order, 'third'));

        $pipeline->handle(new ServerRequest('GET', '/test'));

        $this->assertSame(['first', 'second', 'third'], $order);
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $called = false;
        $fallback = new class($called) implements RequestHandlerInterface {
            public function __construct(
                private bool &$called,
            ) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->called = true;
                return new Response(200);
            }
        };

        $pipeline = new MiddlewarePipeline($fallback);
        $pipeline->pipe(new class() implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                return new Response(403, [], 'blocked');
            }
        });

        $response = $pipeline->handle(new ServerRequest('GET', '/test'));

        $this->assertFalse($called);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testPipeReturnsSelf(): void
    {
        $pipeline = new MiddlewarePipeline($this->createHandler(new Response()));
        $middleware = $this->createHeaderMiddleware('X-Test', 'value');

        $result = $pipeline->pipe($middleware);

        $this->assertSame($pipeline, $result);
    }

    public function testMiddlewareCanModifyRequest(): void
    {
        $capturedAttribute = null;
        $fallback = new class($capturedAttribute) implements RequestHandlerInterface {
            public function __construct(
                private mixed &$captured,
            ) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->captured = $request->getAttribute('added-by-middleware');
                return new Response(200);
            }
        };

        $pipeline = new MiddlewarePipeline($fallback);
        $pipeline->pipe(new class() implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                return $handler->handle($request->withAttribute('added-by-middleware', 'yes'));
            }
        });

        $pipeline->handle(new ServerRequest('GET', '/test'));

        $this->assertSame('yes', $capturedAttribute);
    }

    private function createHandler(ResponseInterface $response): RequestHandlerInterface
    {
        return new class($response) implements RequestHandlerInterface {
            public function __construct(
                private readonly ResponseInterface $response,
            ) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };
    }

    private function createHeaderMiddleware(string $name, string $value): MiddlewareInterface
    {
        return new class($name, $value) implements MiddlewareInterface {
            public function __construct(
                private readonly string $name,
                private readonly string $value,
            ) {}

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                return $handler->handle($request)->withHeader($this->name, $this->value);
            }
        };
    }

    private function createOrderTrackingMiddleware(array &$order, string $label): MiddlewareInterface
    {
        return new class($order, $label) implements MiddlewareInterface {
            public function __construct(
                private array &$order,
                private readonly string $label,
            ) {}

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                $this->order[] = $this->label;
                return $handler->handle($request);
            }
        };
    }
}
