<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\EventSubscriber;

use AppDevPanel\Adapter\Symfony\EventSubscriber\HttpSubscriber;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
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

        $subscriber = new HttpSubscriber($debugger, $requestCollector);

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

        $subscriber = new HttpSubscriber($debugger, $requestCollector);

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

        $subscriber = new HttpSubscriber($debugger, $requestCollector);

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

        $subscriber = new HttpSubscriber($debugger, $requestCollector);

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

        $subscriber = new HttpSubscriber($debugger, exceptionCollector: $exceptionCollector);

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

        $subscriber = new HttpSubscriber($debugger, $requestCollector);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/api/data', 'GET');

        // 1. Request
        $subscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        // 2. Response
        $response = new Response('{"data":true}', 200);
        $subscriber->onKernelResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));

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

        $subscriber = new HttpSubscriber($debugger, $requestCollector);
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

        $subscriber = new HttpSubscriber($debugger, $requestCollector);
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

        $subscriber = new HttpSubscriber($debugger, exceptionCollector: $exceptionCollector);
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = Request::create('/debug/api/data');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, new \RuntimeException('test'));

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

        $subscriber = new HttpSubscriber($debugger, webAppInfoCollector: $webAppInfo);
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = Request::create('/test');
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        $data = $webAppInfo->getCollected();
        $this->assertArrayHasKey('applicationProcessingTime', $data);
    }
}
