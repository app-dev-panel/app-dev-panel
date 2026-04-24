<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\DependencyInjection;

use AppDevPanel\Adapter\Symfony\Controller\AdpAssetController;
use AppDevPanel\Adapter\Symfony\DependencyInjection\AppDevPanelExtension;
use AppDevPanel\Adapter\Symfony\EventSubscriber\ConsoleSubscriber;
use AppDevPanel\Adapter\Symfony\EventSubscriber\HttpSubscriber;
use AppDevPanel\Adapter\Symfony\Inspector\SymfonyConfigProvider;
use AppDevPanel\Adapter\Symfony\Inspector\SymfonyRouteCollectionAdapter;
use AppDevPanel\Adapter\Symfony\Inspector\SymfonyUrlMatcherAdapter;
use AppDevPanel\Api\Inspector\Controller\DatabaseController;
use AppDevPanel\Api\Inspector\Controller\RoutingController;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use AppDevPanel\Api\Panel\PanelConfig;
use AppDevPanel\Api\Toolbar\ToolbarConfig;
use AppDevPanel\FrontendAssets\FrontendAssets;
use AppDevPanel\Kernel\Collector\AssetBundleCollector;
use AppDevPanel\Kernel\Collector\AuthorizationCollector;
use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CodeCoverageCollector;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\RouterCollector;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\TemplateCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\ValidatorCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\StorageInterface;
use AppDevPanel\McpServer\Inspector\InspectorClient;
use AppDevPanel\McpServer\Tool\ToolRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AppDevPanelExtensionTest extends TestCase
{
    public function testLoadRegistersServices(): void
    {
        $container = new ContainerBuilder();
        $extension = new AppDevPanelExtension();

        $extension->load([['enabled' => true]], $container);

        // Core services
        $this->assertTrue($container->hasDefinition(DebuggerIdGenerator::class));
        $this->assertTrue($container->hasDefinition(StorageInterface::class));
        $this->assertTrue($container->hasDefinition(TimelineCollector::class));

        // Event subscribers
        $this->assertTrue($container->hasDefinition(HttpSubscriber::class));
        $this->assertTrue($container->hasDefinition(ConsoleSubscriber::class));
    }

    public function testLoadRegistersAllDefaultCollectors(): void
    {
        $container = new ContainerBuilder();
        $extension = new AppDevPanelExtension();

        $extension->load([['enabled' => true]], $container);

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
            DatabaseCollector::class,
            TemplateCollector::class,
            AuthorizationCollector::class,
            CacheCollector::class,
            MailerCollector::class,
            QueueCollector::class,
            ValidatorCollector::class,
            RouterCollector::class,
            AssetBundleCollector::class,
        ];

        foreach ($expectedCollectors as $collectorClass) {
            $this->assertTrue(
                $container->hasDefinition($collectorClass),
                "Collector {$collectorClass} should be registered",
            );
        }
    }

    public function testLoadWithDisabledDoesNotRegisterAnything(): void
    {
        $container = new ContainerBuilder();
        $extension = new AppDevPanelExtension();

        $extension->load([['enabled' => false]], $container);

        $this->assertFalse($container->hasDefinition(DebuggerIdGenerator::class));
        $this->assertFalse($container->hasDefinition(HttpSubscriber::class));
    }

    public function testLoadWithDisabledCollectors(): void
    {
        $container = new ContainerBuilder();
        $extension = new AppDevPanelExtension();

        $extension->load([
            [
                'enabled' => true,
                'collectors' => [
                    'doctrine' => false,
                    'twig' => false,
                    'security' => false,
                    'mailer' => false,
                    'queue' => false,
                    'validator' => false,
                    'router' => false,
                    'assets' => false,
                ],
            ],
        ], $container);

        $this->assertFalse($container->hasDefinition(DatabaseCollector::class));
        $this->assertFalse($container->hasDefinition(TemplateCollector::class));
        $this->assertFalse($container->hasDefinition(AuthorizationCollector::class));
        $this->assertFalse($container->hasDefinition(MailerCollector::class));
        $this->assertFalse($container->hasDefinition(QueueCollector::class));
        $this->assertFalse($container->hasDefinition(ValidatorCollector::class));
        $this->assertFalse($container->hasDefinition(RouterCollector::class));
        $this->assertFalse($container->hasDefinition(AssetBundleCollector::class));

        // Core collectors still registered
        $this->assertTrue($container->hasDefinition(LogCollector::class));
        $this->assertTrue($container->hasDefinition(RequestCollector::class));
    }

    public function testCollectorsAreTagged(): void
    {
        $container = new ContainerBuilder();
        $extension = new AppDevPanelExtension();

        $extension->load([['enabled' => true]], $container);

        $taggedCollectors = $container->findTaggedServiceIds('app_dev_panel.collector');
        $this->assertNotEmpty($taggedCollectors);
        $this->assertArrayHasKey(TimelineCollector::class, $taggedCollectors);
        $this->assertArrayHasKey(LogCollector::class, $taggedCollectors);
        $this->assertArrayHasKey(RequestCollector::class, $taggedCollectors);
    }

    public function testEventSubscribersAreTagged(): void
    {
        $container = new ContainerBuilder();
        $extension = new AppDevPanelExtension();

        $extension->load([['enabled' => true]], $container);

        $httpDef = $container->getDefinition(HttpSubscriber::class);
        $this->assertTrue($httpDef->hasTag('kernel.event_subscriber'));

        $consoleDef = $container->getDefinition(ConsoleSubscriber::class);
        $this->assertTrue($consoleDef->hasTag('kernel.event_subscriber'));
    }

    public function testParametersAreSet(): void
    {
        $container = new ContainerBuilder();
        $extension = new AppDevPanelExtension();

        $extension->load([
            [
                'enabled' => true,
                'storage' => ['path' => '/custom/path', 'history_size' => 100],
            ],
        ], $container);

        $this->assertTrue($container->getParameter('app_dev_panel.enabled'));
        $this->assertSame('/custom/path', $container->getParameter('app_dev_panel.storage.path'));
        $this->assertSame(100, $container->getParameter('app_dev_panel.storage.history_size'));
    }

    public function testGetAlias(): void
    {
        $extension = new AppDevPanelExtension();

        $this->assertSame('app_dev_panel', $extension->getAlias());
    }

    public function testLoadRegistersInspectorAdapters(): void
    {
        $container = new ContainerBuilder();
        $extension = new AppDevPanelExtension();

        $extension->load([['enabled' => true]], $container);

        $this->assertTrue($container->hasDefinition(SymfonyRouteCollectionAdapter::class));
        $this->assertTrue($container->hasDefinition(SymfonyUrlMatcherAdapter::class));
        $this->assertTrue($container->hasDefinition(SymfonyConfigProvider::class));
        $this->assertTrue($container->hasDefinition(RoutingController::class));
        $this->assertTrue($container->hasDefinition(DatabaseController::class));
        $this->assertTrue($container->hasDefinition(SchemaProviderInterface::class));
        $this->assertTrue($container->hasAlias('config'));
    }

    public function testLoadDoesNotOverrideExistingSchemaProvider(): void
    {
        $container = new ContainerBuilder();
        $container->register(SchemaProviderInterface::class, SchemaProviderInterface::class);
        $extension = new AppDevPanelExtension();

        $extension->load([['enabled' => true]], $container);

        $definition = $container->getDefinition(SchemaProviderInterface::class);
        $this->assertSame(SchemaProviderInterface::class, $definition->getClass());
    }

    public function testRoutingControllerReceivesAdapters(): void
    {
        $container = new ContainerBuilder();
        $extension = new AppDevPanelExtension();

        $extension->load([['enabled' => true]], $container);

        $definition = $container->getDefinition(RoutingController::class);
        $arguments = $definition->getArguments();
        $this->assertCount(3, $arguments);
    }

    public function testLoadRegistersCodeCoverageCollectorWhenEnabled(): void
    {
        $container = new ContainerBuilder();
        $extension = new AppDevPanelExtension();

        $extension->load([
            [
                'enabled' => true,
                'collectors' => ['code_coverage' => true],
            ],
        ], $container);

        $this->assertTrue($container->hasDefinition(CodeCoverageCollector::class));

        $definition = $container->getDefinition(CodeCoverageCollector::class);
        $this->assertTrue($definition->hasTag('app_dev_panel.collector'));
    }

    public function testCodeCoverageCollectorNotRegisteredByDefault(): void
    {
        $container = new ContainerBuilder();
        $extension = new AppDevPanelExtension();

        $extension->load([['enabled' => true]], $container);

        $this->assertFalse($container->hasDefinition(CodeCoverageCollector::class));
    }

    public function testLoadWithApiDisabledDoesNotRegisterApiServices(): void
    {
        $container = new ContainerBuilder();
        $extension = new AppDevPanelExtension();

        $extension->load([
            [
                'enabled' => true,
                'api' => ['enabled' => false],
            ],
        ], $container);

        // Core services should still be registered
        $this->assertTrue($container->hasDefinition(DebuggerIdGenerator::class));

        // API services should not be registered
        $this->assertFalse($container->hasDefinition(SymfonyConfigProvider::class));
        $this->assertFalse($container->hasDefinition(RoutingController::class));
    }

    public function testToolRegistryRegisteredWithoutInspectorClientByDefault(): void
    {
        $container = new ContainerBuilder();
        $extension = new AppDevPanelExtension();

        $extension->load([['enabled' => true]], $container);

        // ToolRegistry should be registered without InspectorClient (no inspector_url configured)
        $this->assertTrue($container->hasDefinition(ToolRegistry::class));
        $this->assertFalse($container->hasDefinition(InspectorClient::class));
    }

    public function testToolRegistryRegisteredWithInspectorClientWhenUrlConfigured(): void
    {
        $container = new ContainerBuilder();
        $extension = new AppDevPanelExtension();

        $extension->load([
            [
                'enabled' => true,
                'api' => ['inspector_url' => 'http://localhost:8080'],
            ],
        ], $container);

        $this->assertTrue($container->hasDefinition(ToolRegistry::class));
        $this->assertTrue($container->hasDefinition(InspectorClient::class));

        $definition = $container->getDefinition(InspectorClient::class);
        $this->assertSame('http://localhost:8080', $definition->getArgument(0));
    }

    public function testAdpAssetControllerIsRegistered(): void
    {
        $container = new ContainerBuilder();
        new AppDevPanelExtension()->load([['enabled' => true]], $container);

        $this->assertTrue($container->hasDefinition(AdpAssetController::class));
        $definition = $container->getDefinition(AdpAssetController::class);
        $this->assertTrue($definition->isPublic());
        $this->assertTrue($definition->hasTag('controller.service_arguments'));
    }

    public function testPanelStaticUrlPrefersFrontendAssetsWhenInstalled(): void
    {
        $distDir = FrontendAssets::path();
        if (!is_dir($distDir)) {
            mkdir($distDir, 0o777, true);
        }
        $indexPath = $distDir . '/index.html';
        $hadIndex = is_file($indexPath);
        if (!$hadIndex) {
            file_put_contents($indexPath, '<!doctype html><title>test</title>');
        }

        try {
            $container = new ContainerBuilder();
            new AppDevPanelExtension()->load([['enabled' => true]], $container);

            $panelConfig = $container->getDefinition(PanelConfig::class);
            $this->assertSame('/debug-assets', $panelConfig->getArgument(0));

            $toolbarConfig = $container->getDefinition(ToolbarConfig::class);
            $this->assertSame('/debug-assets', $toolbarConfig->getArgument(1));
        } finally {
            if (!$hadIndex) {
                unlink($indexPath);
            }
        }
    }

    public function testExplicitPanelStaticUrlOverridesAutoDetect(): void
    {
        $container = new ContainerBuilder();
        new AppDevPanelExtension()->load([
            [
                'enabled' => true,
                'panel' => ['static_url' => 'https://cdn.example.com/adp'],
            ],
        ], $container);

        $panelConfig = $container->getDefinition(PanelConfig::class);
        $this->assertSame('https://cdn.example.com/adp', $panelConfig->getArgument(0));
    }
}
