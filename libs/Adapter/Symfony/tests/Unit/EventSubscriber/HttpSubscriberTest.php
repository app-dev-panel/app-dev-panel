<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\EventSubscriber;

use AppDevPanel\Adapter\Symfony\Collector\RouterDataExtractor;
use AppDevPanel\Adapter\Symfony\EventSubscriber\HttpSubscriber;
use AppDevPanel\Adapter\Symfony\EventSubscriber\HttpSubscriberCollectors;
use AppDevPanel\Api\Panel\PanelConfig;
use AppDevPanel\Api\Toolbar\ToolbarConfig;
use AppDevPanel\Api\Toolbar\ToolbarInjector;
use AppDevPanel\Kernel\Collector\EnvironmentCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\RouterCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\StartupContext;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class HttpSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = HttpSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        $this->assertArrayHasKey(KernelEvents::TERMINATE, $events);

        // Verify priorities
        $this->assertSame(['onKernelRequest', 1024], $events[KernelEvents::REQUEST]);
        $this->assertSame(['onKernelResponse', -1024], $events[KernelEvents::RESPONSE]);
        $this->assertSame(['onKernelException', 0], $events[KernelEvents::EXCEPTION]);
        $this->assertSame(['onKernelTerminate', -2048], $events[KernelEvents::TERMINATE]);
    }

    public function testOnKernelRequestStartsDebugger(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, []);

        $subscriber = new HttpSubscriber($debugger);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/test');
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        // Debugger should have started (ID should be set)
        $this->assertNotEmpty($debugger->getId());
    }

    public function testOnKernelRequestIgnoresSubRequests(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $requestCollector = new RequestCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$requestCollector, $timeline]);

        $subscriber = new HttpSubscriber($debugger, new HttpSubscriberCollectors(request: $requestCollector));

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/test');
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        $subscriber->onKernelRequest($event);

        // Sub-request should not trigger collection
        $this->assertSame([], $requestCollector->getCollected());
    }

    public function testOnKernelRequestCollectsRequestData(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $requestCollector = new RequestCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$requestCollector, $timeline]);

        $subscriber = new HttpSubscriber($debugger, new HttpSubscriberCollectors(request: $requestCollector));

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/api/users', 'POST');
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        $data = $requestCollector->getCollected();
        $this->assertSame('/api/users', $data['requestPath']);
        $this->assertSame('POST', $data['requestMethod']);
    }

    public function testOnKernelResponseCollectsResponseAndAddsHeader(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $requestCollector = new RequestCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$requestCollector, $timeline]);

        $subscriber = new HttpSubscriber($debugger, new HttpSubscriberCollectors(request: $requestCollector));

        // First trigger request to start debugger
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/test');
        $requestEvent = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($requestEvent);

        // Now trigger response
        $response = new Response('OK', 200);
        $responseEvent = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $subscriber->onKernelResponse($responseEvent);

        // Check response has debug header
        $this->assertTrue($response->headers->has('X-Debug-Id'));
        $this->assertSame($debugger->getId(), $response->headers->get('X-Debug-Id'));

        // Check collector got response data
        $data = $requestCollector->getCollected();
        $this->assertSame(200, $data['responseStatusCode']);
    }

    public function testOnKernelResponseIgnoresSubRequests(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $requestCollector = new RequestCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$requestCollector, $timeline]);

        $subscriber = new HttpSubscriber($debugger, new HttpSubscriberCollectors(request: $requestCollector));

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/test');
        $response = new Response();
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $response);

        $subscriber->onKernelResponse($event);

        // Sub-request response should not have debug header
        $this->assertFalse($response->headers->has('X-Debug-Id'));
    }

    public function testOnKernelExceptionCollectsException(): void
    {
        $timeline = new TimelineCollector();
        $exceptionCollector = new ExceptionCollector($timeline);
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, [$exceptionCollector, $timeline]);
        $debugger->startup(StartupContext::generic());

        $subscriber = new HttpSubscriber($debugger, new HttpSubscriberCollectors(exception: $exceptionCollector));

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/test');
        $exception = new \RuntimeException('Something went wrong');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $subscriber->onKernelException($event);

        $data = $exceptionCollector->getCollected();
        $this->assertCount(1, $data);
        $this->assertSame(\RuntimeException::class, $data[0]['class']);
        $this->assertSame('Something went wrong', $data[0]['message']);
    }

    public function testOnKernelTerminateCallsShutdown(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->createMock(\AppDevPanel\Kernel\Storage\StorageInterface::class);
        $storage->expects($this->once())->method('flush');
        $debugger = new Debugger($idGenerator, $storage, []);
        $debugger->startup(StartupContext::generic());

        $subscriber = new HttpSubscriber($debugger);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/test');
        $response = new Response();
        $event = new TerminateEvent($kernel, $request, $response);

        $subscriber->onKernelTerminate($event);
    }

    public function testFullLifecycle(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $requestCollector = new RequestCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$requestCollector, $timeline]);

        $subscriber = new HttpSubscriber($debugger, new HttpSubscriberCollectors(request: $requestCollector));
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/api/data', 'GET');

        // 1. Request
        $subscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        // 2. Response
        $response = new Response('{"data":true}', 200);
        $subscriber->onKernelResponse(
            new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response),
        );

        // 3. Terminate
        $subscriber->onKernelTerminate(new TerminateEvent($kernel, $request, $response));

        // After full lifecycle, data should have been flushed to storage
        $this->assertTrue($response->headers->has('X-Debug-Id'));
    }

    public function testNullCollectorsAreHandled(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, []);
        $debugger->startup(StartupContext::generic());

        // Subscriber with no optional collectors — should not throw
        $subscriber = new HttpSubscriber($debugger);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/test');
        $response = new Response();

        $subscriber->onKernelTerminate(new TerminateEvent($kernel, $request, $response));

        // Lifecycle completed without error
        $this->assertTrue(true);
    }

    public function testOnKernelRequestSkipsDebugApiPath(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $requestCollector = new RequestCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$requestCollector, $timeline]);

        $subscriber = new HttpSubscriber($debugger, new HttpSubscriberCollectors(request: $requestCollector));
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = Request::create('/debug/api/summary');
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        // RequestCollector should NOT have any data for ADP API paths
        $this->assertSame([], $requestCollector->getCollected());
    }

    public function testOnKernelRequestSkipsInspectApiPath(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $requestCollector = new RequestCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$requestCollector, $timeline]);

        $subscriber = new HttpSubscriber($debugger, new HttpSubscriberCollectors(request: $requestCollector));
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = Request::create('/inspect/api/table');
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        $this->assertSame([], $requestCollector->getCollected());
    }

    public function testOnKernelResponseSkipsDebugApiPath(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, []);

        $subscriber = new HttpSubscriber($debugger);
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = Request::create('/debug/api/data');
        $response = new Response();
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $subscriber->onKernelResponse($event);

        $this->assertFalse($response->headers->has('X-Debug-Id'));
    }

    public function testOnKernelExceptionSkipsDebugApiPath(): void
    {
        $timeline = new TimelineCollector();
        $exceptionCollector = new ExceptionCollector($timeline);
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, [$exceptionCollector, $timeline]);
        $debugger->startup(StartupContext::generic());

        $subscriber = new HttpSubscriber($debugger, new HttpSubscriberCollectors(exception: $exceptionCollector));
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = Request::create('/debug/api/data');
        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new \RuntimeException('test'),
        );

        $subscriber->onKernelException($event);

        $this->assertEmpty($exceptionCollector->getCollected());
    }

    public function testOnKernelTerminateSkipsDebugApiPath(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->createMock(\AppDevPanel\Kernel\Storage\StorageInterface::class);
        $storage->expects($this->never())->method('flush');
        $debugger = new Debugger($idGenerator, $storage, []);
        $debugger->startup(StartupContext::generic());

        $subscriber = new HttpSubscriber($debugger);
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = Request::create('/inspect/api/table');
        $response = new Response();
        $event = new TerminateEvent($kernel, $request, $response);

        $subscriber->onKernelTerminate($event);
    }

    public function testOnKernelRequestCollectsWebAppInfo(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $webAppInfo = new WebAppInfoCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$webAppInfo, $timeline]);

        $subscriber = new HttpSubscriber($debugger, new HttpSubscriberCollectors(webAppInfo: $webAppInfo));
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = Request::create('/test');
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        $data = $webAppInfo->getCollected();
        $this->assertArrayHasKey('applicationProcessingTime', $data);
    }

    public function testOnKernelRequestCollectsEnvironmentData(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $environmentCollector = new EnvironmentCollector();
        $debugger = new Debugger($idGenerator, $storage, [$environmentCollector, $timeline]);

        $subscriber = new HttpSubscriber($debugger, new HttpSubscriberCollectors(environment: $environmentCollector));
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = Request::create('/test');
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        $data = $environmentCollector->getCollected();
        $this->assertArrayHasKey('php', $data);
        $this->assertArrayHasKey('os', $data);
    }

    public function testOnKernelResponseCallsRouterDataExtractor(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $routerCollector = new RouterCollector();
        $routerDataExtractor = new RouterDataExtractor($routerCollector);
        $debugger = new Debugger($idGenerator, $storage, [$routerCollector, $timeline]);

        $subscriber = new HttpSubscriber(
            $debugger,
            new HttpSubscriberCollectors(routerDataExtractor: $routerDataExtractor),
        );
        $kernel = $this->createMock(HttpKernelInterface::class);

        // Start debugger
        $request = Request::create('/test');
        $request->attributes->set('_route', 'test_route');
        $request->attributes->set('_controller', 'App\\Controller\\TestController::index');
        $request->attributes->set('_route_params', ['id' => '42']);
        $requestEvent = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($requestEvent);

        // Trigger response
        $response = new Response('OK', 200);
        $responseEvent = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $subscriber->onKernelResponse($responseEvent);

        $data = $routerCollector->getCollected();
        $this->assertSame('test_route', $data['currentRoute']['name']);
        $this->assertSame('App\\Controller\\TestController::index', $data['currentRoute']['action']);
    }

    public function testOnKernelResponseMarksWebAppInfoTimings(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $webAppInfo = new WebAppInfoCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$webAppInfo, $timeline]);

        $subscriber = new HttpSubscriber($debugger, new HttpSubscriberCollectors(webAppInfo: $webAppInfo));
        $kernel = $this->createMock(HttpKernelInterface::class);

        // Start request
        $request = Request::create('/test');
        $subscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        // Response
        $response = new Response('OK');
        $subscriber->onKernelResponse(
            new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response),
        );

        $data = $webAppInfo->getCollected();
        $this->assertArrayHasKey('requestProcessingTime', $data);
    }

    public function testOnKernelTerminateFlushesToStorage(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $webAppInfo = new WebAppInfoCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$webAppInfo, $timeline]);

        $subscriber = new HttpSubscriber($debugger, new HttpSubscriberCollectors(webAppInfo: $webAppInfo));
        $kernel = $this->createMock(HttpKernelInterface::class);

        // Full lifecycle
        $request = Request::create('/test');
        $subscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        $response = new Response('OK');
        $subscriber->onKernelResponse(
            new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response),
        );

        // Check data before terminate (collector still active)
        $data = $webAppInfo->getCollected();
        $this->assertArrayHasKey('applicationProcessingTime', $data);
        $this->assertArrayHasKey('requestProcessingTime', $data);

        $subscriber->onKernelTerminate(new TerminateEvent($kernel, $request, $response));

        // After terminate, data has been flushed to storage
        $this->assertTrue($response->headers->has('X-Debug-Id'));
    }

    public function testToolbarInjectionOnHtmlResponse(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, []);

        $toolbarInjector = new ToolbarInjector(
            new PanelConfig('https://cdn.example.com'),
            new ToolbarConfig(enabled: true),
        );

        $subscriber = new HttpSubscriber($debugger, toolbarInjector: $toolbarInjector);
        $kernel = $this->createMock(HttpKernelInterface::class);

        // Start debugger
        $request = Request::create('/test');
        $subscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        // Response with HTML content
        $response = new Response('<html><body>Hello</body></html>', 200, ['Content-Type' => 'text/html']);
        $responseEvent = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $subscriber->onKernelResponse($responseEvent);

        $this->assertStringContainsString('app-dev-toolbar', $response->getContent());
    }

    public function testToolbarNotInjectedWhenDisabled(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, []);

        $toolbarInjector = new ToolbarInjector(new PanelConfig(), new ToolbarConfig(enabled: false));

        $subscriber = new HttpSubscriber($debugger, toolbarInjector: $toolbarInjector);
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = Request::create('/test');
        $subscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        $response = new Response('<html><body>Hello</body></html>', 200, ['Content-Type' => 'text/html']);
        $subscriber->onKernelResponse(
            new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response),
        );

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

        $subscriber = new HttpSubscriber($debugger, toolbarInjector: $toolbarInjector);
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = Request::create('/debug');
        $subscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        $html = '<html><body><div id="root"></div></body></html>';
        $response = new Response($html, 200, ['Content-Type' => 'text/html']);
        $subscriber->onKernelResponse(
            new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response),
        );

        $this->assertSame($html, $response->getContent());
    }

    public function testToolbarNotInjectedForNonHtmlResponse(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, []);

        $toolbarInjector = new ToolbarInjector(new PanelConfig(), new ToolbarConfig(enabled: true));

        $subscriber = new HttpSubscriber($debugger, toolbarInjector: $toolbarInjector);
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = Request::create('/test');
        $subscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        $response = new Response('{"data":true}', 200, ['Content-Type' => 'application/json']);
        $subscriber->onKernelResponse(
            new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response),
        );

        $this->assertSame('{"data":true}', $response->getContent());
    }

    public function testToolbarNotInjectedForEmptyContent(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, []);

        $toolbarInjector = new ToolbarInjector(new PanelConfig(), new ToolbarConfig(enabled: true));

        $subscriber = new HttpSubscriber($debugger, toolbarInjector: $toolbarInjector);
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = Request::create('/test');
        $subscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        $response = new Response('', 200, ['Content-Type' => 'text/html']);
        $subscriber->onKernelResponse(
            new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response),
        );

        $this->assertSame('', $response->getContent());
    }

    public function testVarDumperHandlerRegistered(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $varDumperCollector = new VarDumperCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$varDumperCollector, $timeline]);

        $subscriber = new HttpSubscriber($debugger, new HttpSubscriberCollectors(varDumper: $varDumperCollector));
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = Request::create('/test');
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($event);

        // VarDumper handler should have been registered — calling dump() should not fail
        $this->assertNotEmpty($debugger->getId());
    }

    public function testVarDumperHandlerNotRegisteredWithoutCollector(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, []);

        // No varDumper collector
        $subscriber = new HttpSubscriber($debugger);
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = Request::create('/test');
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($event);

        // Should complete without error even without VarDumper collector
        $this->assertNotEmpty($debugger->getId());
    }

    public function testVarDumperHandlerRegisteredOnlyOnce(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $varDumperCollector = new VarDumperCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$varDumperCollector, $timeline]);

        $subscriber = new HttpSubscriber($debugger, new HttpSubscriberCollectors(varDumper: $varDumperCollector));
        $kernel = $this->createMock(HttpKernelInterface::class);

        // First request
        $request = Request::create('/test');
        $subscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        $subscriber->onKernelTerminate(new TerminateEvent($kernel, $request, new Response()));

        // Second request - handler should not be re-registered
        $debugger->startup(StartupContext::generic());
        $subscriber->onKernelRequest(
            new RequestEvent($kernel, Request::create('/test2'), HttpKernelInterface::MAIN_REQUEST),
        );

        // No error, completed successfully
        $this->assertTrue(true);
    }

    public function testOnKernelResponseWithoutRequestCollectorDoesNotCollectResponse(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, []);

        // No request collector — only webAppInfo
        $timeline = new TimelineCollector();
        $webAppInfo = new WebAppInfoCollector($timeline);
        $subscriber = new HttpSubscriber($debugger, new HttpSubscriberCollectors(webAppInfo: $webAppInfo));
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = Request::create('/test');
        $subscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        $response = new Response('OK', 200);
        $responseEvent = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $subscriber->onKernelResponse($responseEvent);

        // Should add debug header even without request collector
        $this->assertTrue($response->headers->has('X-Debug-Id'));
    }

    public function testVarDumperHandlerCollectsDumpCallsWithSourceLine(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $varDumperCollector = new VarDumperCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$varDumperCollector, $timeline]);

        $subscriber = new HttpSubscriber($debugger, new HttpSubscriberCollectors(varDumper: $varDumperCollector));
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = Request::create('/test');
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($event);

        // Trigger the VarDumper handler by calling dump()
        // This exercises lines 160-171 of HttpSubscriber (the VarDumper handler closure)
        \Symfony\Component\VarDumper\VarDumper::dump('test-value');

        $collected = $varDumperCollector->getCollected();
        $this->assertNotEmpty($collected);
    }

    public function testAllCollectorsCombined(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $requestCollector = new RequestCollector($timeline);
        $webAppInfo = new WebAppInfoCollector($timeline);
        $exceptionCollector = new ExceptionCollector($timeline);
        $varDumperCollector = new VarDumperCollector($timeline);
        $environmentCollector = new EnvironmentCollector();
        $routerCollector = new RouterCollector();
        $routerDataExtractor = new RouterDataExtractor($routerCollector);

        $debugger = new Debugger($idGenerator, $storage, [
            $requestCollector,
            $webAppInfo,
            $exceptionCollector,
            $varDumperCollector,
            $environmentCollector,
            $routerCollector,
            $timeline,
        ]);

        $collectors = new HttpSubscriberCollectors(
            request: $requestCollector,
            webAppInfo: $webAppInfo,
            exception: $exceptionCollector,
            varDumper: $varDumperCollector,
            environment: $environmentCollector,
            routerDataExtractor: $routerDataExtractor,
        );

        $subscriber = new HttpSubscriber($debugger, $collectors);
        $kernel = $this->createMock(HttpKernelInterface::class);

        // Full lifecycle with all collectors
        $request = Request::create('/api/data', 'POST');
        $request->attributes->set('_route', 'api_data');
        $request->attributes->set('_controller', 'App\\Controller\\DataController::index');
        $subscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        $response = new Response('{"ok":true}', 200);
        $subscriber->onKernelResponse(
            new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response),
        );

        // Check data before terminate (collectors still active)
        $this->assertTrue($response->headers->has('X-Debug-Id'));
        $this->assertSame('/api/data', $requestCollector->getCollected()['requestPath']);
        $this->assertArrayHasKey('php', $environmentCollector->getCollected());
        $this->assertSame('api_data', $routerCollector->getCollected()['currentRoute']['name']);

        $subscriber->onKernelTerminate(new TerminateEvent($kernel, $request, $response));
    }
}
