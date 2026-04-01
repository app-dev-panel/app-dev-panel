<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\DependencyInjection;

use AppDevPanel\Adapter\Symfony\DependencyInjection\AppDevPanelExtension;
use AppDevPanel\Adapter\Symfony\DependencyInjection\CollectorProxyCompilerPass;
use AppDevPanel\Adapter\Symfony\Proxy\SymfonyEventDispatcherProxy;
use AppDevPanel\Adapter\Symfony\Proxy\SymfonyTranslatorProxy;
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

    public function testDecoratesTranslator(): void
    {
        $container = $this->createLoadedContainer();

        $container->register('translator', \Symfony\Contracts\Translation\TranslatorInterface::class);

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition(SymfonyTranslatorProxy::class));
    }

    public function testSkipsTranslatorDecorationWhenCollectorDisabled(): void
    {
        $container = $this->createLoadedContainer(['translator' => false]);

        $container->register('translator', \Symfony\Contracts\Translation\TranslatorInterface::class);

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition(SymfonyTranslatorProxy::class));
    }

    public function testSkipsTranslatorDecorationWhenServiceMissing(): void
    {
        $container = $this->createLoadedContainer();

        // Don't register translator service

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition(SymfonyTranslatorProxy::class));
    }

    public function testSkipsEventDispatcherDecorationWhenCollectorDisabled(): void
    {
        $container = $this->createLoadedContainer(['event' => false]);

        $container->register('event_dispatcher', \Symfony\Component\EventDispatcher\EventDispatcher::class);

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition(SymfonyEventDispatcherProxy::class));
    }

    public function testSkipsHttpClientDecorationWhenCollectorDisabled(): void
    {
        $container = $this->createLoadedContainer(['http_client' => false]);

        $container->register(ClientInterface::class, ClientInterface::class);

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        // HttpClientInterfaceProxy should NOT be registered when HttpClientCollector is disabled
        // However registerApiServices may register its own ClientInterface. Check that
        // the proxy isn't decorating the user-provided one.
        // With http_client disabled, HttpClientCollector isn't registered, so proxy is skipped
        $this->assertFalse($container->hasDefinition(HttpClientInterfaceProxy::class));
    }

    public function testDecoratesTranslatorViaFqcnFallback(): void
    {
        $container = $this->createLoadedContainer();

        // Register translator via FQCN, not 'translator' service ID
        $container->register(
            \Symfony\Contracts\Translation\TranslatorInterface::class,
            \Symfony\Contracts\Translation\TranslatorInterface::class,
        );

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition(SymfonyTranslatorProxy::class));
    }

    public function testCollectsContainerParametersSorted(): void
    {
        $container = $this->createLoadedContainer();

        $container->setParameter('zebra', 'z-value');
        $container->setParameter('alpha', 'a-value');

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        $resolvedParams = $container->getParameter('app_dev_panel.container_parameters');
        $keys = array_keys($resolvedParams);

        // Verify alphabetical sorting: 'alpha' should come before 'zebra'
        $alphaIndex = array_search('alpha', $keys);
        $zebraIndex = array_search('zebra', $keys);
        $this->assertLessThan($zebraIndex, $alphaIndex);
    }

    public function testCollectsContainerParametersSkipsWhenNoInspectController(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('app_dev_panel.enabled', true);
        $container->setParameter('app_dev_panel.ignored_requests', []);
        $container->setParameter('app_dev_panel.ignored_commands', []);
        $container->setParameter('custom_param', 'value');

        // No InspectController registered, but the pass should not fail
        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        // Parameters should still be collected even without InspectController
        $this->assertTrue($container->hasParameter('app_dev_panel.container_parameters'));
    }

    public function testDecoratesLoggerPrefersSymfonyCanonical(): void
    {
        $container = $this->createLoadedContainer();

        // Register both 'logger' and Psr\Log\LoggerInterface
        $container->register('logger', \Psr\Log\NullLogger::class);
        $container->register(LoggerInterface::class, LoggerInterface::class);

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition(LoggerInterfaceProxy::class));

        // The proxy should decorate 'logger' (preferred), not LoggerInterface FQCN
        $proxyDef = $container->getDefinition(LoggerInterfaceProxy::class);
        $this->assertSame('logger', $proxyDef->getDecoratedService()[0]);
    }

    public function testDecoratesTranslatorPrefersCanonicalServiceId(): void
    {
        $container = $this->createLoadedContainer();

        // Register both 'translator' and TranslatorInterface FQCN
        $container->register('translator', \Symfony\Contracts\Translation\TranslatorInterface::class);
        $container->register(
            \Symfony\Contracts\Translation\TranslatorInterface::class,
            \Symfony\Contracts\Translation\TranslatorInterface::class,
        );

        $pass = new CollectorProxyCompilerPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition(SymfonyTranslatorProxy::class));

        // Should decorate 'translator' (preferred), not the FQCN
        $proxyDef = $container->getDefinition(SymfonyTranslatorProxy::class);
        $this->assertSame('translator', $proxyDef->getDecoratedService()[0]);
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
