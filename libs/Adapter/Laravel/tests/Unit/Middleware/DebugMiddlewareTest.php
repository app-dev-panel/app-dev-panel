<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Middleware;

use AppDevPanel\Adapter\Laravel\Middleware\DebugCollectors;
use AppDevPanel\Adapter\Laravel\Middleware\DebugMiddleware;
use AppDevPanel\Api\Panel\PanelConfig;
use AppDevPanel\Api\Toolbar\ToolbarConfig;
use AppDevPanel\Api\Toolbar\ToolbarInjector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\VarDumper;

final class DebugMiddlewareTest extends TestCase
{
    public function testSkipsDebugApiRequests(): void
    {
        [$middleware] = $this->createMiddleware();

        $request = Request::create('/debug/api/list');
        $expectedResponse = new Response('OK');

        $response = $middleware->handle($request, static fn() => $expectedResponse);

        // Debug API requests are passed through without modification
        $this->assertSame($expectedResponse, $response);
        $this->assertFalse($response->headers->has('X-Debug-Id'));
    }

    public function testSkipsInspectApiRequests(): void
    {
        [$middleware] = $this->createMiddleware();

        $request = Request::create('/inspect/api/config');
        $expectedResponse = new Response('OK');

        $response = $middleware->handle($request, static fn() => $expectedResponse);

        $this->assertSame($expectedResponse, $response);
        $this->assertFalse($response->headers->has('X-Debug-Id'));
    }

    public function testHandleStartsDebuggerAndAddsDebugHeader(): void
    {
        [$middleware] = $this->createMiddleware();

        $request = Request::create('/test-page', 'GET');
        $expectedResponse = new Response('Hello', 200);

        $response = $middleware->handle($request, static fn() => $expectedResponse);

        $this->assertTrue($response->headers->has('X-Debug-Id'));
        $this->assertNotEmpty($response->headers->get('X-Debug-Id'));
    }

    public function testTerminateSkipsDebugApiPaths(): void
    {
        [$middleware, , $storage] = $this->createMiddleware();

        // Terminate with a debug API path without prior handle — should not throw
        $request = Request::create('/debug/api/list');
        $middleware->terminate($request, new Response('OK'));

        // Verify no entries were written to storage
        $ref = new \ReflectionProperty($storage, 'entries');
        $this->assertEmpty($ref->getValue($storage));
    }

    public function testTerminateCallsShutdownAndFlushesData(): void
    {
        [$middleware, , $storage] = $this->createMiddleware();

        $request = Request::create('/test');
        $expectedResponse = new Response('OK');
        $middleware->handle($request, static fn() => $expectedResponse);

        $middleware->terminate($request, $expectedResponse);

        $summaries = $storage->read($storage::TYPE_SUMMARY);
        $this->assertNotEmpty($summaries);
    }

    public function testHandleCollectsExceptionOnThrow(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $exceptionCollector = new ExceptionCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$exceptionCollector, $timeline]);

        $middleware = new DebugMiddleware(
            debugger: $debugger,
            collectors: new DebugCollectors(exception: $exceptionCollector),
        );

        $request = Request::create('/error');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test error');

        $middleware->handle($request, static function (): never {
            throw new \RuntimeException('Test error');
        });
    }

    public function testToolbarInjectionOnHtmlResponse(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $requestCollector = new RequestCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$requestCollector, $timeline]);

        $toolbarInjector = new ToolbarInjector(
            new PanelConfig('https://cdn.example.com'),
            new ToolbarConfig(enabled: true),
        );

        $middleware = new DebugMiddleware(
            debugger: $debugger,
            collectors: new DebugCollectors(request: $requestCollector),
            toolbarInjector: $toolbarInjector,
        );

        $request = Request::create('/test');
        $response = $middleware->handle($request, static fn() => new Response('<html><body>Hello</body></html>', 200, [
            'Content-Type' => 'text/html',
        ]));

        $this->assertStringContainsString('app-dev-toolbar', $response->getContent());
    }

    public function testToolbarNotInjectedWhenDisabled(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, []);

        $toolbarInjector = new ToolbarInjector(new PanelConfig(), new ToolbarConfig(enabled: false));

        $middleware = new DebugMiddleware(debugger: $debugger, toolbarInjector: $toolbarInjector);

        $request = Request::create('/test');
        $response = $middleware->handle($request, static fn() => new Response('<html><body>Hello</body></html>', 200, [
            'Content-Type' => 'text/html',
        ]));

        $this->assertStringNotContainsString('app-dev-toolbar', $response->getContent());
    }

    public function testToolbarNotInjectedForPanelRequest(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, []);

        $toolbarInjector = new ToolbarInjector(
            new PanelConfig(staticUrl: '/assets', viewerBasePath: '/debug'),
            new ToolbarConfig(enabled: true),
        );

        $middleware = new DebugMiddleware(debugger: $debugger, toolbarInjector: $toolbarInjector);

        $request = Request::create('/debug');
        $html = '<html><body><div id="root"></div></body></html>';
        $response = $middleware->handle($request, static fn() => new Response($html, 200, [
            'Content-Type' => 'text/html',
        ]));

        $this->assertSame($html, $response->getContent());
    }

    public function testToolbarNotInjectedForNonHtmlResponse(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, []);

        $toolbarInjector = new ToolbarInjector(new PanelConfig(), new ToolbarConfig(enabled: true));

        $middleware = new DebugMiddleware(debugger: $debugger, toolbarInjector: $toolbarInjector);

        $request = Request::create('/api/test');
        $response = $middleware->handle($request, static fn() => new Response('{"data":true}', 200, [
            'Content-Type' => 'application/json',
        ]));

        $this->assertSame('{"data":true}', $response->getContent());
    }

    public function testToolbarNotInjectedForEmptyContent(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, []);

        $toolbarInjector = new ToolbarInjector(new PanelConfig(), new ToolbarConfig(enabled: true));

        $middleware = new DebugMiddleware(debugger: $debugger, toolbarInjector: $toolbarInjector);

        $request = Request::create('/test');
        $response = $middleware->handle($request, static fn() => new Response('', 200, [
            'Content-Type' => 'text/html',
        ]));

        $this->assertSame('', $response->getContent());
    }

    public function testVarDumperHandlerCollectsDumpCalls(): void
    {
        // Reset the static flag before this test
        $reflection = new \ReflectionClass(DebugMiddleware::class);
        $prop = $reflection->getProperty('varDumperHandlerRegistered');
        $prop->setValue(null, false);

        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $varDumperCollector = new VarDumperCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$varDumperCollector, $timeline]);

        $middleware = new DebugMiddleware(
            debugger: $debugger,
            collectors: new DebugCollectors(varDumper: $varDumperCollector),
        );

        $request = Request::create('/test');
        $middleware->handle($request, static fn() => new Response('OK'));

        // Trigger the VarDumper handler to exercise the callback code path
        VarDumper::dump('test-value');

        $collected = $varDumperCollector->getCollected();
        $this->assertNotEmpty($collected);
    }

    /**
     * @return array{DebugMiddleware, Debugger, MemoryStorage}
     */
    private function createMiddleware(): array
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $requestCollector = new RequestCollector($timeline);
        $webAppInfoCollector = new WebAppInfoCollector($timeline, 'Laravel');
        $debugger = new Debugger($idGenerator, $storage, [$requestCollector, $webAppInfoCollector, $timeline]);

        $middleware = new DebugMiddleware(
            debugger: $debugger,
            collectors: new DebugCollectors(request: $requestCollector, webAppInfo: $webAppInfoCollector),
        );

        return [$middleware, $debugger, $storage];
    }
}
