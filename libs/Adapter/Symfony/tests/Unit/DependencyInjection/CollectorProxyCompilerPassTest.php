<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\DependencyInjection;

use AppDevPanel\Adapter\Symfony\DependencyInjection\AppDevPanelExtension;
use AppDevPanel\Adapter\Symfony\DependencyInjection\CollectorProxyCompilerPass;
use AppDevPanel\Adapter\Symfony\Proxy\SymfonyEventDispatcherProxy;
use AppDevPanel\Api\Inspector\Controller\InspectController;
use AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy;
use AppDevPanel\Kernel\Collector\LoggerInterfaceProxy;
use AppDevPanel\Kernel\Debugger;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class CollectorProxyCompilerPassTest extends TestCase
{
    public function testRegistersDebuggerWithCollectors(): void
    {
        $container = $this->createLoadedContainer();

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition(Debugger::class));

        $debuggerDef = $container->getDefinition(Debugger::class);
        $this->assertTrue($debuggerDef->isPublic());

        // Should have collector references as third argument
        $collectors = $debuggerDef->getArgument(2);
        $this->assertIsArray($collectors);
        $this->assertNotEmpty($collectors);
    }

    public function testDecoratesLoggerInterface(): void
    {
        $container = $this->createLoadedContainer();

        // Register a mock LoggerInterface
        $container->register(LoggerInterface::class, LoggerInterface::class);

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition(LoggerInterfaceProxy::class));
    }

    public function testDecoratesEventDispatcher(): void
    {
        $container = $this->createLoadedContainer();

        $container->register('event_dispatcher', \Symfony\Component\EventDispatcher\EventDispatcher::class);

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition(SymfonyEventDispatcherProxy::class));
    }

    public function testDecoratesHttpClient(): void
    {
        $container = $this->createLoadedContainer();

        $container->register(ClientInterface::class, ClientInterface::class);

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition(HttpClientInterfaceProxy::class));
    }

    public function testSkipsDecorationWhenServiceMissing(): void
    {
        $container = $this->createLoadedContainer();

        // Don't register LoggerInterface or event_dispatcher.
        // Note: ClientInterface is registered by registerApiServices(), so
        // HttpClientInterfaceProxy will be created. We only check Logger and EventDispatcher.

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        // Logger and EventDispatcher proxies should not be registered (no underlying service)
        $this->assertFalse($container->hasDefinition(LoggerInterfaceProxy::class));
        $this->assertFalse($container->hasDefinition(SymfonyEventDispatcherProxy::class));

        // HttpClient proxy IS registered because registerApiServices() provides ClientInterface
        $this->assertTrue($container->hasDefinition(HttpClientInterfaceProxy::class));
    }

    public function testSkipsWhenNotEnabled(): void
    {
        $container = new ContainerBuilder();
        // Don't set app_dev_panel.enabled parameter

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition(Debugger::class));
    }

    public function testDecoratesLoggerViaSymfonyServiceId(): void
    {
        $container = $this->createLoadedContainer();

        // Register logger as 'logger' (Symfony canonical), not Psr\Log\LoggerInterface
        $container->register('logger', \Psr\Log\NullLogger::class);

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition(LoggerInterfaceProxy::class));
    }

    public function testSkipsLoggerDecorationWhenCollectorDisabled(): void
    {
        $container = $this->createLoadedContainer(['log' => false]);

        $container->register(LoggerInterface::class, LoggerInterface::class);

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        // LogCollector not registered, so proxy should not be applied
        $this->assertFalse($container->hasDefinition(LoggerInterfaceProxy::class));
    }

    public function testCollectsContainerParametersIntoInspectController(): void
    {
        $container = $this->createLoadedContainer();

        // Simulate Symfony kernel parameters
        $container->setParameter('kernel.project_dir', '/app');
        $container->setParameter('kernel.environment', 'dev');
        $container->setParameter('kernel.debug', true);
        $container->setParameter('locale', 'en');

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        // InspectController should have params as 3rd argument
        $def = $container->getDefinition(InspectController::class);
        $params = $def->getArgument(2);

        $this->assertIsArray($params);
        $this->assertArrayHasKey('kernel.project_dir', $params);
        $this->assertSame('/app', $params['kernel.project_dir']);
        $this->assertArrayHasKey('locale', $params);
        $this->assertSame('en', $params['locale']);
    }

    public function testContainerParametersExcludesAdpInternalParams(): void
    {
        $container = $this->createLoadedContainer();

        $container->setParameter('kernel.project_dir', '/app');

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        $def = $container->getDefinition(InspectController::class);
        $params = $def->getArgument(2);

        // app_dev_panel.* parameters should be excluded
        foreach (array_keys($params) as $key) {
            $this->assertStringStartsNotWith('app_dev_panel.', (string) $key);
        }
    }

    public function testContainerParametersPassedToConfigProvider(): void
    {
        $container = $this->createLoadedContainer();

        $container->setParameter('kernel.project_dir', '/app');
        $container->setParameter('database_url', 'sqlite:///var/data.db');

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        $resolvedParams = $container->getParameter('app_dev_panel.container_parameters');
        $this->assertIsArray($resolvedParams);
        $this->assertArrayHasKey('kernel.project_dir', $resolvedParams);
        $this->assertArrayHasKey('database_url', $resolvedParams);
    }

    private function createLoadedContainer(array $collectorOverrides = []): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $extension = new AppDevPanelExtension();

        $config = ['enabled' => true];
        if ($collectorOverrides !== []) {
            $config['collectors'] = $collectorOverrides;
        }

        $extension->load([$config], $container);

        return $container;
    }
}
