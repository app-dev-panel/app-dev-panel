<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\DependencyInjection;

use AppDevPanel\Adapter\Symfony\DependencyInjection\AppDevPanelExtension;
use AppDevPanel\Adapter\Symfony\EventSubscriber\ConsoleSubscriber;
use AppDevPanel\Adapter\Symfony\EventSubscriber\HttpSubscriber;
use AppDevPanel\Adapter\Symfony\Inspector\SymfonyConfigProvider;
use AppDevPanel\Adapter\Symfony\Inspector\SymfonyRouteCollectionAdapter;
use AppDevPanel\Adapter\Symfony\Inspector\SymfonyUrlMatcherAdapter;
use AppDevPanel\Api\Inspector\Controller\DatabaseController;
use AppDevPanel\Api\Inspector\Controller\RoutingController;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\RouterCollector;
use AppDevPanel\Kernel\Collector\AuthorizationCollector;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\TemplateCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\ValidatorCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\StorageInterface;
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
                    'messenger' => false,
                    'validator' => false,
                    'router' => false,
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
}
