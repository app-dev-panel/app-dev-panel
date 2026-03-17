<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\DependencyInjection;

use AppDevPanel\Adapter\Symfony\DependencyInjection\AppDevPanelExtension;
use AppDevPanel\Adapter\Symfony\DependencyInjection\CollectorProxyCompilerPass;
use AppDevPanel\Adapter\Symfony\Proxy\SymfonyEventDispatcherProxy;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy;
use AppDevPanel\Kernel\Collector\LogCollector;
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

    public function testSkipsLoggerDecorationWhenCollectorDisabled(): void
    {
        $container = $this->createLoadedContainer(['log' => false]);

        $container->register(LoggerInterface::class, LoggerInterface::class);

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        // LogCollector not registered, so proxy should not be applied
        $this->assertFalse($container->hasDefinition(LoggerInterfaceProxy::class));
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
