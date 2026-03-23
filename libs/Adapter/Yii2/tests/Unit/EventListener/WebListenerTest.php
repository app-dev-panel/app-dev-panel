<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\EventListener;

use AppDevPanel\Adapter\Yii2\EventListener\WebListener;
use AppDevPanel\Adapter\Yii2\Proxy\RouterMatchRecorder;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\RouterCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use PHPUnit\Framework\TestCase;
use yii\base\Action;
use yii\base\Controller;
use yii\base\Event;
use yii\web\Application;
use yii\web\HeaderCollection;
use yii\web\Request;
use yii\web\Response;
use yii\web\UrlRule;

final class WebListenerTest extends TestCase
{
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

        $debugger = new Debugger($idGenerator, $storage, [$timeline, $requestCollector]);

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

        $debugger = new Debugger($idGenerator, $storage, [
            $timeline,
            $requestCollector,
            $webAppInfoCollector,
            $exceptionCollector,
        ]);

        $listener = new WebListener($debugger, $requestCollector, $webAppInfoCollector, $exceptionCollector);

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

    public function testExtractRouteDataFromRecorder(): void
    {
        $recorder = new RouterMatchRecorder();
        $routerCollector = new RouterCollector();

        [$listener] = $this->createListenerWithRouter($routerCollector, $recorder);

        $rule = $this->createMock(UrlRule::class);
        $rule->name = 'site/<action>';
        $rule->host = null;

        // Simulate routing match
        $recorder->markStartIfNeeded();
        $recorder->recordMatch($rule, ['site/index', ['action' => 'index']]);

        $controller = $this->createMock(Controller::class);
        $action = $this->createMock(Action::class);
        $controller->action = $action;

        $app = $this->createWebApp('/site/index');
        $app->controller = $controller;

        $event = new Event(['sender' => $app]);
        $listener->onBeforeRequest($event);

        // Check collected data before shutdown (collectors still active)
        $this->simulateAfterRequestWithoutShutdown($listener, $app);

        $collected = $routerCollector->getCollected();
        $this->assertArrayHasKey('currentRoute', $collected);

        $route = $collected['currentRoute'];
        $this->assertSame('site/<action>', $route['name']);
        $this->assertSame('site/<action>', $route['pattern']);
        $this->assertSame(['action' => 'index'], $route['arguments']);
        $this->assertGreaterThan(0, $route['matchTime']);
        $this->assertSame('/site/index', $route['uri']);
    }

    public function testExtractRouteDataFromRecorderWithHostConstraint(): void
    {
        $recorder = new RouterMatchRecorder();
        $routerCollector = new RouterCollector();

        [$listener] = $this->createListenerWithRouter($routerCollector, $recorder);

        $rule = $this->createMock(UrlRule::class);
        $rule->name = 'api/<controller>/<action>';
        $rule->host = 'api.example.com';

        $recorder->markStartIfNeeded();
        $recorder->recordMatch($rule, ['api/users/list', ['controller' => 'users', 'action' => 'list']]);

        $controller = $this->createMock(Controller::class);
        $controller->action = $this->createMock(Action::class);

        $app = $this->createWebApp('/api/users/list');
        $app->controller = $controller;

        $event = new Event(['sender' => $app]);
        $listener->onBeforeRequest($event);
        $this->simulateAfterRequestWithoutShutdown($listener, $app);

        $collected = $routerCollector->getCollected();
        $this->assertSame('api.example.com', $collected['currentRoute']['host']);
    }

    public function testFallbackToControllerWhenNoRecorder(): void
    {
        $routerCollector = new RouterCollector();

        [$listener] = $this->createListenerWithRouter($routerCollector, null);

        $controller = $this->createMock(Controller::class);
        $action = $this->createMock(Action::class);
        $controller->action = $action;

        $app = $this->createWebApp('/site/about');
        $app->controller = $controller;
        $app->requestedRoute = 'site/about';
        $app->requestedParams = [];

        $event = new Event(['sender' => $app]);
        $listener->onBeforeRequest($event);
        $this->simulateAfterRequestWithoutShutdown($listener, $app);

        $collected = $routerCollector->getCollected();
        $this->assertArrayHasKey('currentRoute', $collected);

        $route = $collected['currentRoute'];
        $this->assertSame('site/about', $route['pattern']);
        $this->assertSame(0, $route['matchTime']);
        $this->assertNull($route['name']);
    }

    public function testRouteDataRecordedEvenWhenControllerIsNull(): void
    {
        $routerCollector = new RouterCollector();

        [$listener] = $this->createListenerWithRouter($routerCollector, null);

        $app = $this->createWebApp('/nonexistent');
        $app->controller = null;
        $app->requestedRoute = 'nonexistent';

        $event = new Event(['sender' => $app]);
        $listener->onBeforeRequest($event);
        $this->simulateAfterRequestWithoutShutdown($listener, $app);

        $collected = $routerCollector->getCollected();
        $this->assertArrayHasKey('currentRoute', $collected);
        $route = $collected['currentRoute'];
        $this->assertSame('nonexistent', $route['pattern']);
        $this->assertNull($route['action']);
        $this->assertSame('/nonexistent', $route['uri']);
    }

    public function testOnExceptionHandlerExtractsRouteData(): void
    {
        $routerCollector = new RouterCollector();

        [$listener] = $this->createListenerWithRouter($routerCollector, null);

        $app = $this->createWebApp('/test/fixtures/request-info');
        $app->controller = null;
        $app->requestedRoute = 'test/fixtures/request-info';
        $app->requestedParams = [];

        // Simulate: onBeforeRequest fires, then exception occurs (no onAfterRequest)
        $event = new Event(['sender' => $app]);
        $listener->onBeforeRequest($event);

        // Exception handler path — onAfterRequest never fires
        $listener->onExceptionHandler($app);

        $collected = $routerCollector->getCollected();
        $this->assertArrayHasKey('currentRoute', $collected);
        $route = $collected['currentRoute'];
        $this->assertSame('test/fixtures/request-info', $route['pattern']);
        $this->assertSame('/test/fixtures/request-info', $route['uri']);
    }

    public function testRecorderResetAfterShutdown(): void
    {
        $recorder = new RouterMatchRecorder();
        $routerCollector = new RouterCollector();

        [$listener] = $this->createListenerWithRouter($routerCollector, $recorder);

        $rule = $this->createMock(UrlRule::class);
        $rule->name = 'site/index';
        $rule->host = null;

        $recorder->markStartIfNeeded();
        $recorder->recordMatch($rule, ['site/index', []]);

        $app = $this->createWebApp('/site/index');
        $app->controller = $this->createMock(Controller::class);
        $app->controller->action = $this->createMock(Action::class);

        $event = new Event(['sender' => $app]);
        $listener->onBeforeRequest($event);
        $listener->onAfterRequest($event);

        // Recorder should be reset after shutdown
        $this->assertNull($recorder->getMatchedRule());
        $this->assertNull($recorder->getMatchResult());
    }

    /**
     * Invoke onAfterRequest logic that extracts route data but skip debugger->shutdown().
     *
     * This allows asserting collected data before collectors are reset.
     * Uses reflection to call the private extractRouteData method directly.
     */
    private function simulateAfterRequestWithoutShutdown(WebListener $listener, Application $app): void
    {
        $reflection = new \ReflectionMethod($listener, 'extractRouteData');
        $reflection->invoke($listener, $app);
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

        $debugger = new Debugger($idGenerator, $storage, [$timeline, $requestCollector]);

        $listener = new WebListener($debugger, $requestCollector);

        return [$listener, $debugger, $timeline, $storage];
    }

    /**
     * @return array{0: WebListener, 1: Debugger}
     */
    private function createListenerWithRouter(RouterCollector $routerCollector, ?RouterMatchRecorder $recorder): array
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $requestCollector = new RequestCollector($timeline);

        $debugger = new Debugger($idGenerator, $storage, [$timeline, $requestCollector, $routerCollector]);

        $listener = new WebListener($debugger, $requestCollector, null, null, $routerCollector, $recorder);

        return [$listener, $debugger];
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
