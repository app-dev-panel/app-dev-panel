<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\DependencyInjection;

use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\LoggerInterfaceProxy;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\StorageInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that:
 * 1. Collects all tagged collectors and injects them into the Debugger
 * 2. Decorates PSR services (Logger, EventDispatcher, HttpClient) with proxies
 */
final class CollectorProxyCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('app_dev_panel.enabled')) {
            return;
        }

        $this->registerDebugger($container);
        $this->decorateLogger($container);
        $this->decorateEventDispatcher($container);
        $this->decorateHttpClient($container);
    }

    private function registerDebugger(ContainerBuilder $container): void
    {
        $collectorRefs = [];
        foreach ($container->findTaggedServiceIds('app_dev_panel.collector') as $id => $tags) {
            $collectorRefs[] = new Reference($id);
        }

        $container->register(Debugger::class, Debugger::class)
            ->setArguments([
                new Reference(DebuggerIdGenerator::class),
                new Reference(StorageInterface::class),
                $collectorRefs,
                '%app_dev_panel.ignored_requests%',
                '%app_dev_panel.ignored_commands%',
            ])
            ->setPublic(true);
    }

    private function decorateLogger(ContainerBuilder $container): void
    {
        if (!$container->has(LoggerInterface::class) || !$container->has(LogCollector::class)) {
            return;
        }

        $container->register('app_dev_panel.logger.inner', LoggerInterface::class)
            ->setDecoratedService(LoggerInterface::class, null, -10);

        $container->register(LoggerInterfaceProxy::class, LoggerInterfaceProxy::class)
            ->setDecoratedService(LoggerInterface::class, null, -9)
            ->setArguments([
                new Reference(LoggerInterfaceProxy::class . '.inner'),
                new Reference(LogCollector::class),
            ]);
    }

    private function decorateEventDispatcher(ContainerBuilder $container): void
    {
        if (!$container->has(EventDispatcherInterface::class) || !$container->has(EventCollector::class)) {
            return;
        }

        $container->register(EventDispatcherInterfaceProxy::class, EventDispatcherInterfaceProxy::class)
            ->setDecoratedService(EventDispatcherInterface::class, null, -10)
            ->setArguments([
                new Reference(EventDispatcherInterfaceProxy::class . '.inner'),
                new Reference(EventCollector::class),
            ]);
    }

    private function decorateHttpClient(ContainerBuilder $container): void
    {
        if (!$container->has(ClientInterface::class) || !$container->has(HttpClientCollector::class)) {
            return;
        }

        $container->register(HttpClientInterfaceProxy::class, HttpClientInterfaceProxy::class)
            ->setDecoratedService(ClientInterface::class, null, -10)
            ->setArguments([
                new Reference(HttpClientInterfaceProxy::class . '.inner'),
                new Reference(HttpClientCollector::class),
            ]);
    }
}
