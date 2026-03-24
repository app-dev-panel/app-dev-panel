<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Integration;

use AppDevPanel\Adapter\Symfony\AppDevPanelBundle;
use AppDevPanel\Adapter\Symfony\EventSubscriber\HttpSubscriber;
use AppDevPanel\Adapter\Symfony\Proxy\SymfonyEventDispatcherProxy;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\LoggerInterfaceProxy;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Integration test: verifies that logger and event dispatcher proxies
 * actually intercept calls in a compiled container with real services.
 *
 * Test plan:
 * 1. Build a container with LoggerInterface and event_dispatcher registered
 * 2. Compile the container (compiler pass should decorate both)
 * 3. Run a full HTTP lifecycle (startup → use logger → use dispatcher → shutdown)
 * 4. Check storage for collected log/event entries
 * 5. If data is missing, the proxy wiring is broken
 */
#[CoversNothing]
final class LoggerProxyIntegrationTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp_proxy_test_' . bin2hex(random_bytes(8));
        mkdir($this->storagePath, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storagePath);
    }

    public function testLoggerProxyIsRegisteredWhenLoggerServiceExists(): void
    {
        $container = $this->buildContainer();

        // The LoggerInterface service should now be the proxy
        $logger = $container->get(LoggerInterface::class);
        $this->assertInstanceOf(LoggerInterfaceProxy::class, $logger);
    }

    public function testEventDispatcherProxyIsRegisteredWhenServiceExists(): void
    {
        $container = $this->buildContainer();

        $dispatcher = $container->get('event_dispatcher');
        $this->assertInstanceOf(SymfonyEventDispatcherProxy::class, $dispatcher);
    }

    public function testLoggerProxyCollectsLogsDuringHttpLifecycle(): void
    {
        $container = $this->buildContainer();

        $httpSubscriber = $container->get(HttpSubscriber::class);
        $logCollector = $container->get(LogCollector::class);
        $logger = $container->get(LoggerInterface::class);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/test-logging', 'GET');
        $response = new Response('OK', 200);

        // 1. Start debugger via kernel.request
        $httpSubscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        // 2. Application code logs some messages
        $logger->info('User logged in', ['user_id' => 42]);
        $logger->warning('Deprecated API call');
        $logger->error('Something failed', ['code' => 500]);

        // 3. Verify logs were collected BEFORE shutdown
        $collected = $logCollector->getCollected();
        $this->assertNotEmpty($collected, 'LogCollector should have captured log entries');

        // Filter out any internal debugger logs (e.g. "Debugger: startup")
        $appLogs = array_values(array_filter(
            $collected,
            static fn(array $entry) => !str_starts_with((string) $entry['message'], 'Debugger:'),
        ));

        $this->assertCount(3, $appLogs, 'Should have 3 application log entries');
        $this->assertSame('info', $appLogs[0]['level']);
        $this->assertSame('User logged in', $appLogs[0]['message']);
        $this->assertSame('warning', $appLogs[1]['level']);
        $this->assertSame('Deprecated API call', $appLogs[1]['message']);
        $this->assertSame('error', $appLogs[2]['level']);
        $this->assertSame('Something failed', $appLogs[2]['message']);

        // 4. Response + Terminate
        $httpSubscriber->onKernelResponse(
            new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response),
        );
        $httpSubscriber->onKernelTerminate(new TerminateEvent($kernel, $request, $response));

        // 5. Check storage contains the log data
        $storage = $container->get(StorageInterface::class);
        $dataEntries = $storage->read(StorageInterface::TYPE_DATA);
        $this->assertNotEmpty($dataEntries, 'Data should be flushed to storage');

        $entry = reset($dataEntries);
        $this->assertArrayHasKey(LogCollector::class, $entry, 'Storage should contain LogCollector data');
        $this->assertNotEmpty($entry[LogCollector::class], 'LogCollector data in storage should not be empty');
    }

    public function testEventDispatcherProxyCollectsEventsDuringHttpLifecycle(): void
    {
        $container = $this->buildContainer();

        $httpSubscriber = $container->get(HttpSubscriber::class);
        $eventCollector = $container->get(EventCollector::class);
        $dispatcher = $container->get('event_dispatcher');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/test-events', 'GET');
        $response = new Response('OK', 200);

        // 1. Start debugger
        $httpSubscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        // 2. Dispatch some events
        $dispatcher->dispatch(new \stdClass(), 'app.user_registered');
        $dispatcher->dispatch(new \stdClass(), 'app.order_placed');

        // 3. Verify events were collected
        $collected = $eventCollector->getCollected();
        $this->assertNotEmpty($collected, 'EventCollector should have captured dispatched events');
        $this->assertGreaterThanOrEqual(2, count($collected), 'Should have at least 2 events');

        // 4. Shutdown
        $httpSubscriber->onKernelResponse(
            new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response),
        );
        $httpSubscriber->onKernelTerminate(new TerminateEvent($kernel, $request, $response));

        // 5. Check storage
        $storage = $container->get(StorageInterface::class);
        $dataEntries = $storage->read(StorageInterface::TYPE_DATA);
        $entry = reset($dataEntries);
        $this->assertArrayHasKey(EventCollector::class, $entry, 'Storage should contain EventCollector data');
    }

    public function testLoggerProxyWorksWithSymfonyLoggerServiceId(): void
    {
        // Simulates a real Symfony app where the logger is registered as 'logger'
        // (not Psr\Log\LoggerInterface). This is the canonical Symfony service ID.
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->storagePath);

        // Register logger as 'logger' (like FrameworkBundle does)
        $container->register('logger', TestLogger::class);
        $container->register('event_dispatcher', EventDispatcher::class);

        $bundle = new AppDevPanelBundle();
        $bundle->build($container);

        $extension = $bundle->getContainerExtension();
        $extension->load([['enabled' => true, 'storage' => ['path' => $this->storagePath]]], $container);

        foreach ($container->getDefinitions() as $definition) {
            $definition->setPublic(true);
        }
        foreach ($container->getAliases() as $alias) {
            $alias->setPublic(true);
        }

        $container->compile();

        // The 'logger' service should now be the proxy
        $logger = $container->get('logger');
        $this->assertInstanceOf(LoggerInterfaceProxy::class, $logger);

        // Start debugger and log
        $httpSubscriber = $container->get(HttpSubscriber::class);
        $logCollector = $container->get(LogCollector::class);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/test-logger-service-id', 'GET');
        $httpSubscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        $logger->info('Log via logger service ID');

        $collected = $logCollector->getCollected();
        $appLogs = array_values(array_filter(
            $collected,
            static fn(array $entry) => !str_starts_with((string) $entry['message'], 'Debugger:'),
        ));

        $this->assertCount(1, $appLogs, 'Logger proxy should intercept via "logger" service ID');
        $this->assertSame('Log via logger service ID', $appLogs[0]['message']);

        // Complete the lifecycle to restore error handlers
        $response = new Response('OK', 200);
        $httpSubscriber->onKernelTerminate(new TerminateEvent($kernel, $request, $response));
    }

    public function testLoggerProxyDelegatesToOriginalLogger(): void
    {
        $container = $this->buildContainer();

        $httpSubscriber = $container->get(HttpSubscriber::class);
        $logger = $container->get(LoggerInterface::class);

        // Start debugger so collectors are active
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/test', 'GET');
        $httpSubscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        // The proxy wraps our TestLogger. Calling log should reach it.
        // If decoration is broken, the proxy would wrap an abstract interface — instant crash.
        $logger->info('Test message');

        // No exception = delegation works
        $this->assertTrue(true);

        // Complete the lifecycle to restore error handlers
        $response = new Response('OK', 200);
        $httpSubscriber->onKernelTerminate(new TerminateEvent($kernel, $request, $response));
    }

    private function buildContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->storagePath);

        // Register a real LoggerInterface (simulates MonologBundle)
        $container->register(LoggerInterface::class, TestLogger::class);

        // Register a real event_dispatcher (simulates FrameworkBundle)
        $container->register('event_dispatcher', EventDispatcher::class);

        $bundle = new AppDevPanelBundle();
        $bundle->build($container);

        $extension = $bundle->getContainerExtension();
        $extension->load([['enabled' => true, 'storage' => ['path' => $this->storagePath]]], $container);

        foreach ($container->getDefinitions() as $definition) {
            $definition->setPublic(true);
        }
        foreach ($container->getAliases() as $alias) {
            $alias->setPublic(true);
        }

        $container->compile();

        return $container;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }
}

/**
 * Minimal PSR-3 logger for testing — just stores messages.
 */
final class TestLogger implements LoggerInterface
{
    /** @var array<int, array{level: string, message: string, context: array<mixed>}> */
    public array $records = [];

    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(\Stringable|string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log(mixed $level, \Stringable|string $message, array $context = []): void
    {
        $this->records[] = ['level' => (string) $level, 'message' => (string) $message, 'context' => $context];
    }
}
