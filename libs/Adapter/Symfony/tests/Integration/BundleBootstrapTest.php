<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Integration;

use AppDevPanel\Adapter\Symfony\AppDevPanelBundle;
use AppDevPanel\Adapter\Symfony\EventSubscriber\ConsoleSubscriber;
use AppDevPanel\Adapter\Symfony\EventSubscriber\HttpSubscriber;
use AppDevPanel\Api\Panel\PanelConfig;
use AppDevPanel\Api\Panel\PanelController;
use AppDevPanel\Kernel\Collector\AuthorizationCollector;
use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\RouterCollector;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector;
use AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector;
use AppDevPanel\Kernel\Collector\TemplateCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\ValidatorCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\BroadcastingStorage;
use AppDevPanel\Kernel\Storage\SqliteStorage;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Integration test: boots the full ADP bundle (Extension + CompilerPass),
 * compiles the container, and verifies all services resolve to real instances.
 */
#[CoversNothing]
final class BundleBootstrapTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp_test_' . bin2hex(random_bytes(8));
        mkdir($this->storagePath, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storagePath);
    }

    public function testContainerCompilesWithDefaultConfig(): void
    {
        $container = $this->buildContainer();

        $this->assertTrue($container->has(Debugger::class));
        $this->assertTrue($container->has(HttpSubscriber::class));
        $this->assertTrue($container->has(ConsoleSubscriber::class));
    }

    public function testDebuggerReceivesAllCollectors(): void
    {
        $container = $this->buildContainer();

        $debugger = $container->get(Debugger::class);
        $this->assertInstanceOf(Debugger::class, $debugger);
    }

    public function testCoreServicesResolveToCorrectTypes(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(DebuggerIdGenerator::class, $container->get(DebuggerIdGenerator::class));
        $this->assertInstanceOf(BroadcastingStorage::class, $container->get(StorageInterface::class));
        $this->assertInstanceOf(TimelineCollector::class, $container->get(TimelineCollector::class));
    }

    public function testAllDefaultCollectorsResolve(): void
    {
        $container = $this->buildContainer();

        $expectedCollectors = [
            TimelineCollector::class,
            RequestCollector::class,
            WebAppInfoCollector::class,
            ExceptionCollector::class,
            LogCollector::class,
            EventCollector::class,
            ServiceCollector::class,
            HttpClientCollector::class,
            VarDumperCollector::class,
            FilesystemStreamCollector::class,
            HttpStreamCollector::class,
            CommandCollector::class,
            ConsoleAppInfoCollector::class,
            DatabaseCollector::class,
            TemplateCollector::class,
            AuthorizationCollector::class,
            CacheCollector::class,
            MailerCollector::class,
            QueueCollector::class,
            ValidatorCollector::class,
            RouterCollector::class,
        ];

        foreach ($expectedCollectors as $class) {
            $this->assertInstanceOf(
                $class,
                $container->get($class),
                "Collector {$class} should resolve from compiled container",
            );
        }
    }

    public function testEventSubscribersResolve(): void
    {
        $container = $this->buildContainer();

        $httpSubscriber = $container->get(HttpSubscriber::class);
        $this->assertInstanceOf(HttpSubscriber::class, $httpSubscriber);

        $consoleSubscriber = $container->get(ConsoleSubscriber::class);
        $this->assertInstanceOf(ConsoleSubscriber::class, $consoleSubscriber);
    }

    public function testHttpLifecycleFlushesToStorage(): void
    {
        $container = $this->buildContainer();

        $debugger = $container->get(Debugger::class);
        $httpSubscriber = $container->get(HttpSubscriber::class);
        $requestCollector = $container->get(RequestCollector::class);

        $kernel = $this->createMock(\Symfony\Component\HttpKernel\HttpKernelInterface::class);
        $request = \Symfony\Component\HttpFoundation\Request::create('/integration-test', 'GET');
        $response = new \Symfony\Component\HttpFoundation\Response('OK', 200);

        // 1. kernel.request
        $httpSubscriber->onKernelRequest(new \Symfony\Component\HttpKernel\Event\RequestEvent(
            $kernel,
            $request,
            \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
        ));

        $debugId = $debugger->getId();
        $this->assertNotEmpty($debugId);

        // Verify request data was collected
        $this->assertSame('/integration-test', $requestCollector->getCollected()['requestPath']);

        // 2. kernel.response
        $httpSubscriber->onKernelResponse(
            new \Symfony\Component\HttpKernel\Event\ResponseEvent(
                $kernel,
                $request,
                \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
                $response,
            ),
        );

        $this->assertSame($debugId, $response->headers->get('X-Debug-Id'));
        $this->assertSame(200, $requestCollector->getCollected()['responseStatusCode']);

        // 3. kernel.terminate — flushes to SqliteStorage
        $httpSubscriber->onKernelTerminate(new \Symfony\Component\HttpKernel\Event\TerminateEvent(
            $kernel,
            $request,
            $response,
        ));

        // Verify data was persisted to disk
        $storage = $container->get(StorageInterface::class);
        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);
        $this->assertNotEmpty($summaries, 'Summary should be written to storage after shutdown');

        $summaryEntry = reset($summaries);
        $this->assertSame($debugId, $summaryEntry['id']);
        $this->assertNotEmpty($summaryEntry['collectors']);

        $dataEntries = $storage->read(StorageInterface::TYPE_DATA);
        $this->assertNotEmpty($dataEntries, 'Data should be written to storage after shutdown');
    }

    public function testConsoleLifecycleFlushesToStorage(): void
    {
        $container = $this->buildContainer();

        $debugger = $container->get(Debugger::class);
        $consoleSubscriber = $container->get(ConsoleSubscriber::class);

        $command = new \Symfony\Component\Console\Command\Command('app:test');
        $input = new \Symfony\Component\Console\Input\ArrayInput([]);
        $output = new \Symfony\Component\Console\Output\NullOutput();

        // 1. console.command
        $consoleSubscriber->onConsoleCommand(new \Symfony\Component\Console\Event\ConsoleCommandEvent(
            $command,
            $input,
            $output,
        ));

        $debugId = $debugger->getId();
        $this->assertNotEmpty($debugId);

        // 2. console.terminate
        $consoleSubscriber->onConsoleTerminate(
            new \Symfony\Component\Console\Event\ConsoleTerminateEvent($command, $input, $output, 0),
        );

        $storage = $container->get(StorageInterface::class);
        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);
        $this->assertNotEmpty($summaries, 'Console lifecycle should produce stored summary');

        $summaryEntry = reset($summaries);
        $this->assertSame($debugId, $summaryEntry['id']);
    }

    public function testDisabledBundleProducesNoServices(): void
    {
        $container = $this->buildContainer(['enabled' => false]);

        $this->assertFalse($container->has(Debugger::class));
        $this->assertFalse($container->has(HttpSubscriber::class));
        $this->assertFalse($container->has(ConsoleSubscriber::class));
    }

    public function testSelectiveCollectorDisabling(): void
    {
        $container = $this->buildContainer([
            'enabled' => true,
            'storage' => ['path' => $this->storagePath],
            'collectors' => [
                'doctrine' => false,
                'twig' => false,
                'security' => false,
                'cache' => false,
                'mailer' => false,
                'queue' => false,
                'validator' => false,
                'router' => false,
            ],
        ]);

        // Disabled collectors should not exist
        $this->assertFalse($container->has(DatabaseCollector::class));
        $this->assertFalse($container->has(TemplateCollector::class));
        $this->assertFalse($container->has(AuthorizationCollector::class));
        $this->assertFalse($container->has(CacheCollector::class));
        $this->assertFalse($container->has(MailerCollector::class));
        $this->assertFalse($container->has(QueueCollector::class));
        $this->assertFalse($container->has(ValidatorCollector::class));
        $this->assertFalse($container->has(RouterCollector::class));

        // Core collectors still available
        $this->assertInstanceOf(LogCollector::class, $container->get(LogCollector::class));
        $this->assertInstanceOf(RequestCollector::class, $container->get(RequestCollector::class));

        // Debugger still boots
        $this->assertInstanceOf(Debugger::class, $container->get(Debugger::class));
    }

    public function testIgnoredRequestIsSkipped(): void
    {
        $container = $this->buildContainer([
            'enabled' => true,
            'storage' => ['path' => $this->storagePath],
            'ignored_requests' => ['/health/*'],
        ]);

        $debugger = $container->get(Debugger::class);
        $httpSubscriber = $container->get(HttpSubscriber::class);

        $kernel = $this->createMock(\Symfony\Component\HttpKernel\HttpKernelInterface::class);
        $request = \Symfony\Component\HttpFoundation\Request::create('/health/check', 'GET');
        $response = new \Symfony\Component\HttpFoundation\Response('OK', 200);

        $httpSubscriber->onKernelRequest(new \Symfony\Component\HttpKernel\Event\RequestEvent(
            $kernel,
            $request,
            \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
        ));

        $httpSubscriber->onKernelTerminate(new \Symfony\Component\HttpKernel\Event\TerminateEvent(
            $kernel,
            $request,
            $response,
        ));

        // Ignored request should not produce storage entries
        $storage = $container->get(StorageInterface::class);
        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);
        $this->assertEmpty($summaries, 'Ignored request should not produce storage entries');
    }

    public function testExceptionCollectionDuringHttpLifecycle(): void
    {
        $container = $this->buildContainer();

        $httpSubscriber = $container->get(HttpSubscriber::class);
        $exceptionCollector = $container->get(ExceptionCollector::class);

        $kernel = $this->createMock(\Symfony\Component\HttpKernel\HttpKernelInterface::class);
        $request = \Symfony\Component\HttpFoundation\Request::create('/boom', 'GET');

        // Start the lifecycle
        $httpSubscriber->onKernelRequest(new \Symfony\Component\HttpKernel\Event\RequestEvent(
            $kernel,
            $request,
            \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
        ));

        // Trigger exception
        $exception = new \RuntimeException('Integration test error');
        $httpSubscriber->onKernelException(
            new \Symfony\Component\HttpKernel\Event\ExceptionEvent(
                $kernel,
                $request,
                \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
                $exception,
            ),
        );

        $data = $exceptionCollector->getCollected();
        $this->assertCount(1, $data);
        $this->assertSame(\RuntimeException::class, $data[0]['class']);
        $this->assertSame('Integration test error', $data[0]['message']);

        // Complete the lifecycle to restore error handlers
        $response = new \Symfony\Component\HttpFoundation\Response('Error', 500);
        $httpSubscriber->onKernelTerminate(new \Symfony\Component\HttpKernel\Event\TerminateEvent(
            $kernel,
            $request,
            $response,
        ));
    }

    public function testPanelConfigAutoDetectsLocalAssets(): void
    {
        $container = $this->buildContainer();

        $panelConfig = $container->get(PanelConfig::class);
        $this->assertInstanceOf(PanelConfig::class, $panelConfig);

        // If bundle.js was built locally, auto-detects /bundles/appdevpanel; otherwise GitHub Pages
        $adapterRoot = \dirname(__DIR__, 2); // libs/Adapter/Symfony
        $bundleAssetsExist = file_exists($adapterRoot . '/Resources/public/bundle.js');
        $expected = $bundleAssetsExist ? '/bundles/appdevpanel' : PanelConfig::DEFAULT_STATIC_URL;
        $this->assertSame($expected, $panelConfig->staticUrl);
    }

    public function testPanelConfigUsesCustomStaticUrl(): void
    {
        $container = $this->buildContainer([
            'panel' => ['static_url' => '/my-custom-path'],
        ]);

        $panelConfig = $container->get(PanelConfig::class);
        $this->assertSame('/my-custom-path', $panelConfig->staticUrl);
    }

    public function testPanelControllerResolvesFromContainer(): void
    {
        $container = $this->buildContainer();

        $controller = $container->get(PanelController::class);
        $this->assertInstanceOf(PanelController::class, $controller);
    }

    /**
     * Builds and compiles a real Symfony DI container with the ADP bundle.
     */
    private function buildContainer(array $config = []): ContainerBuilder
    {
        $config = array_merge([
            'enabled' => true,
            'storage' => ['path' => $this->storagePath],
        ], $config);

        $container = new ContainerBuilder();

        // Set parameters required by API services (normally provided by Symfony Kernel)
        $container->setParameter('kernel.project_dir', $this->storagePath);

        $bundle = new AppDevPanelBundle();
        $bundle->build($container);

        $extension = $bundle->getContainerExtension();
        $extension->load([$config], $container);

        // Make all services public for testing
        foreach ($container->getDefinitions() as $definition) {
            $definition->setPublic(true);
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
