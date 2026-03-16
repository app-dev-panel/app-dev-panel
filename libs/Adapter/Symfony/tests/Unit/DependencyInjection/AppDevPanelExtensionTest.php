<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\DependencyInjection;

use AppDevPanel\Adapter\Symfony\Collector\CacheCollector;
use AppDevPanel\Adapter\Symfony\Collector\DoctrineCollector;
use AppDevPanel\Adapter\Symfony\Collector\MailerCollector;
use AppDevPanel\Adapter\Symfony\Collector\MessengerCollector;
use AppDevPanel\Adapter\Symfony\Collector\SecurityCollector;
use AppDevPanel\Adapter\Symfony\Collector\SymfonyExceptionCollector;
use AppDevPanel\Adapter\Symfony\Collector\SymfonyRequestCollector;
use AppDevPanel\Adapter\Symfony\Collector\TwigCollector;
use AppDevPanel\Adapter\Symfony\DependencyInjection\AppDevPanelExtension;
use AppDevPanel\Adapter\Symfony\EventSubscriber\ConsoleSubscriber;
use AppDevPanel\Adapter\Symfony\EventSubscriber\HttpSubscriber;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
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
            SymfonyRequestCollector::class,
            WebAppInfoCollector::class,
            SymfonyExceptionCollector::class,
            LogCollector::class,
            EventCollector::class,
            ServiceCollector::class,
            HttpClientCollector::class,
            VarDumperCollector::class,
            DoctrineCollector::class,
            TwigCollector::class,
            SecurityCollector::class,
            CacheCollector::class,
            MailerCollector::class,
            MessengerCollector::class,
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
                ],
            ],
        ], $container);

        $this->assertFalse($container->hasDefinition(DoctrineCollector::class));
        $this->assertFalse($container->hasDefinition(TwigCollector::class));
        $this->assertFalse($container->hasDefinition(SecurityCollector::class));
        $this->assertFalse($container->hasDefinition(MailerCollector::class));
        $this->assertFalse($container->hasDefinition(MessengerCollector::class));

        // Core collectors still registered
        $this->assertTrue($container->hasDefinition(LogCollector::class));
        $this->assertTrue($container->hasDefinition(SymfonyRequestCollector::class));
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
        $this->assertArrayHasKey(SymfonyRequestCollector::class, $taggedCollectors);
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
}
