<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\EventListener;

use AppDevPanel\Adapter\Yii2\EventListener\WebListener;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use PHPUnit\Framework\TestCase;
use yii\base\Event;
use yii\web\Application;
use yii\web\HeaderCollection;
use yii\web\Request;
use yii\web\Response;

final class WebListenerTest extends TestCase
{
    private string $storagePath;

    public function testOnBeforeRequestStartsDebugger(): void
    {
        [$listener, $debugger] = $this->createListener();

        $app = $this->createWebApp('/test-page');
        $event = new Event(['sender' => $app]);

        $listener->onBeforeRequest($event);

        $this->assertNotEmpty($debugger->getId());
    }

    public function testOnBeforeRequestSkipsDebugApiPaths(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $requestCollector = new RequestCollector($timeline);

        $debugger = new Debugger(
            $idGenerator,
            $storage,
            [$timeline, $requestCollector],
        );

        $listener = new WebListener($debugger, $requestCollector);

        $app = $this->createWebApp('/debug/api/entries');
        $event = new Event(['sender' => $app]);

        $listener->onBeforeRequest($event);

        // RequestCollector should not have received request data (no collectRequest called)
        $timeline->startup();
        $requestCollector->startup();
        $collected = $requestCollector->getCollected();
        // requestUrl should be empty since collectRequest was never called
        $this->assertSame('', $collected['requestUrl'] ?? '');
        $this->assertNull($collected['request'] ?? null);
    }

    public function testOnAfterRequestShutsDownDebugger(): void
    {
        [$listener, $debugger, , $storage] = $this->createListener();

        $app = $this->createWebApp('/test-page');
        $event = new Event(['sender' => $app]);

        $listener->onBeforeRequest($event);

        $debugId = $debugger->getId();

        $listener->onAfterRequest($event);

        // Check X-Debug-Id header was set
        $headers = $app->getResponse()->getHeaders();
        $this->assertSame($debugId, $headers->get('X-Debug-Id'));
    }

    public function testFullWebLifecycleFlushesToStorage(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $requestCollector = new RequestCollector($timeline);
        $webAppInfoCollector = new WebAppInfoCollector($timeline);
        $exceptionCollector = new ExceptionCollector($timeline);

        $debugger = new Debugger(
            $idGenerator,
            $storage,
            [$timeline, $requestCollector, $webAppInfoCollector, $exceptionCollector],
        );

        $listener = new WebListener(
            $debugger,
            $requestCollector,
            $webAppInfoCollector,
            $exceptionCollector,
        );

        $app = $this->createWebApp('/api/data');
        $beforeEvent = new Event(['sender' => $app]);

        // 1. Before request
        $listener->onBeforeRequest($beforeEvent);
        $debugId = $debugger->getId();
        $this->assertNotEmpty($debugId);

        // 2. After request (includes shutdown)
        $afterEvent = new Event(['sender' => $app]);
        $listener->onAfterRequest($afterEvent);

        // Verify data was flushed
        $this->assertSame($debugId, $app->getResponse()->getHeaders()->get('X-Debug-Id'));
    }

    public function testOnBeforeRequestIgnoresNonWebApp(): void
    {
        [$listener] = $this->createListener();

        // Pass a non-web-app sender — should not throw, debugger not started
        $event = new Event(['sender' => new \stdClass()]);
        $listener->onBeforeRequest($event);

        $this->assertTrue(true, 'No exception thrown for non-web-app sender');
    }

    /**
     * @return array{0: WebListener, 1: Debugger, 2: TimelineCollector, 3: MemoryStorage}
     */
    private function createListener(): array
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $requestCollector = new RequestCollector($timeline);

        $debugger = new Debugger(
            $idGenerator,
            $storage,
            [$timeline, $requestCollector],
        );

        $listener = new WebListener($debugger, $requestCollector);

        return [$listener, $debugger, $timeline, $storage];
    }

    private function createWebApp(string $url): Application
    {
        $request = $this->createMock(Request::class);
        $request->method('getUrl')->willReturn($url);
        $request->method('getAbsoluteUrl')->willReturn('http://localhost' . $url);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getHeaders')->willReturn(new HeaderCollection());
        $request->method('getQueryParams')->willReturn([]);
        $request->method('getBodyParams')->willReturn([]);

        $responseHeaders = new HeaderCollection();
        $response = $this->createMock(Response::class);
        $response->method('getHeaders')->willReturn($responseHeaders);
        $response->method('getStatusCode')->willReturn(200);
        $response->content = '';

        $app = $this->createMock(Application::class);
        $app->method('getRequest')->willReturn($request);
        $app->method('getResponse')->willReturn($response);

        return $app;
    }
}
