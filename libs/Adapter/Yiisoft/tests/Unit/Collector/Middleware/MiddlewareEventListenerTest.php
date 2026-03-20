<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Tests\Unit\Collector\Middleware;

use AppDevPanel\Adapter\Yiisoft\Collector\Middleware\MiddlewareEventListener;
use AppDevPanel\Kernel\Collector\MiddlewareCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;

final class MiddlewareEventListenerTest extends TestCase
{
    public function testCollectBeforeMiddlewareEvent(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new MiddlewareCollector($timeline);
        $collector->startup();
        $listener = new MiddlewareEventListener($collector);

        $middleware1 = $this->createNamedMiddleware();
        $middleware2 = $this->createNamedMiddleware();
        $request = $this->createMock(ServerRequestInterface::class);

        // Two before events: first goes to beforeStack, second becomes actionHandler
        $listener->collect(new BeforeMiddleware($middleware1, $request));
        $listener->collect(new BeforeMiddleware($middleware2, $request));

        $collected = $collector->getCollected();
        // With 2 before events and 0 after, beforeStack has first one, actionHandler is empty
        // (actionHandler needs both before and after)
        $this->assertCount(1, $collected['beforeStack']);
        $this->assertSame($middleware1::class, $collected['beforeStack'][0]['name']);
        $this->assertSame($request, $collected['beforeStack'][0]['request']);
    }

    public function testCollectFullMiddlewareStack(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new MiddlewareCollector($timeline);
        $collector->startup();
        $listener = new MiddlewareEventListener($collector);

        $outerMiddleware = $this->createNamedMiddleware();
        $actionMiddleware = $this->createNamedMiddleware();
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        // Before: outer → action (innermost)
        $listener->collect(new BeforeMiddleware($outerMiddleware, $request));
        $listener->collect(new BeforeMiddleware($actionMiddleware, $request));

        // After: action (first out) → outer
        $listener->collect(new AfterMiddleware($actionMiddleware, $response));
        $listener->collect(new AfterMiddleware($outerMiddleware, $response));

        $collected = $collector->getCollected();

        // beforeStack = [outer], actionHandler = action, afterStack = [outer]
        $this->assertCount(1, $collected['beforeStack']);
        $this->assertSame($outerMiddleware::class, $collected['beforeStack'][0]['name']);
        $this->assertNotEmpty($collected['actionHandler']);
        $this->assertSame($actionMiddleware::class, $collected['actionHandler']['name']);
        $this->assertSame($response, $collected['actionHandler']['response']);
        $this->assertCount(1, $collected['afterStack']);
        $this->assertSame($outerMiddleware::class, $collected['afterStack'][0]['name']);
    }

    public function testCollectUpdatesTimeline(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new MiddlewareCollector($timeline);
        $collector->startup();
        $listener = new MiddlewareEventListener($collector);

        $middleware = $this->createNamedMiddleware();
        $request = $this->createMock(ServerRequestInterface::class);

        $listener->collect(new BeforeMiddleware($middleware, $request));

        $this->assertCount(1, $timeline->getCollected());
    }

    public function testResolveMiddlewareNameForAnonymousClassWithDebugInfoArrayCallback(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new MiddlewareCollector($timeline);
        $collector->startup();
        $listener = new MiddlewareEventListener($collector);

        // Anonymous class with __debugInfo returning array callback [string, string]
        $middleware = new class() implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                return $handler->handle($request);
            }

            public function __debugInfo(): array
            {
                return ['callback' => ['SomeController', 'index']];
            }
        };
        $request = $this->createMock(ServerRequestInterface::class);
        $listener->collect(new BeforeMiddleware($middleware, $request));

        // This middleware has an action-like before entry
        // We add a second middleware and its after event to get proper structure
        $middleware2 = $this->createNamedMiddleware();
        $listener->collect(new BeforeMiddleware($middleware2, $request));
        $response = $this->createMock(ResponseInterface::class);
        $listener->collect(new AfterMiddleware($middleware2, $response));
        $listener->collect(new AfterMiddleware($middleware, $response));

        $collected = $collector->getCollected();
        $this->assertSame('SomeController::index', $collected['beforeStack'][0]['name']);
    }

    public function testResolveMiddlewareNameForAnonymousClassWithStringCallback(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new MiddlewareCollector($timeline);
        $collector->startup();
        $listener = new MiddlewareEventListener($collector);

        $middleware = new class() implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                return $handler->handle($request);
            }

            public function __debugInfo(): array
            {
                return ['callback' => 'App\Controller\HomeController::index'];
            }
        };
        $request = $this->createMock(ServerRequestInterface::class);

        // Need two before + two after to get first entry in beforeStack
        $listener->collect(new BeforeMiddleware($middleware, $request));
        $middleware2 = $this->createNamedMiddleware();
        $listener->collect(new BeforeMiddleware($middleware2, $request));
        $response = $this->createMock(ResponseInterface::class);
        $listener->collect(new AfterMiddleware($middleware2, $response));
        $listener->collect(new AfterMiddleware($middleware, $response));

        $collected = $collector->getCollected();
        $this->assertSame('{closure:App\Controller\HomeController::index}', $collected['beforeStack'][0]['name']);
    }

    public function testResolveMiddlewareNameForRegularClass(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new MiddlewareCollector($timeline);
        $collector->startup();
        $listener = new MiddlewareEventListener($collector);

        $middleware = $this->createNamedMiddleware();
        $request = $this->createMock(ServerRequestInterface::class);

        $listener->collect(new BeforeMiddleware($middleware, $request));
        $middleware2 = $this->createNamedMiddleware();
        $listener->collect(new BeforeMiddleware($middleware2, $request));
        $response = $this->createMock(ResponseInterface::class);
        $listener->collect(new AfterMiddleware($middleware2, $response));
        $listener->collect(new AfterMiddleware($middleware, $response));

        $collected = $collector->getCollected();
        $this->assertSame($middleware::class, $collected['beforeStack'][0]['name']);
    }

    private function createNamedMiddleware(): MiddlewareInterface
    {
        return new class() implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };
    }
}
