<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\DependencyInjection;

use AppDevPanel\Adapter\Symfony\Proxy\SymfonyEventDispatcherProxy;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\LoggerInterfaceProxy;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\StorageInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
        if (!$container->has(LogCollector::class)) {
            return;
        }

        // Symfony registers the logger as Psr\Log\LoggerInterface (via MonologBundle alias)
        if (!$container->has(LoggerInterface::class)) {
            return;
        }

        $container->register(LoggerInterfaceProxy::class, LoggerInterfaceProxy::class)
            ->setDecoratedService(LoggerInterface::class)
            ->setArguments([
                new Reference(LoggerInterfaceProxy::class . '.inner'),
                new Reference(LogCollector::class),
            ]);
    }

    private function decorateEventDispatcher(ContainerBuilder $container): void
    {
        if (!$container->has(EventCollector::class)) {
            return;
        }

        // Symfony registers the event dispatcher as 'event_dispatcher' service.
        // We decorate it with SymfonyEventDispatcherProxy which implements
        // Symfony\Contracts\EventDispatcher\EventDispatcherInterface (extends PSR-14)
        // and correctly forwards the $eventName parameter.
        if (!$container->has('event_dispatcher')) {
            return;
        }

        $container->register(SymfonyEventDispatcherProxy::class, SymfonyEventDispatcherProxy::class)
            ->setDecoratedService('event_dispatcher')
            ->setArguments([
                new Reference(SymfonyEventDispatcherProxy::class . '.inner'),
                new Reference(EventCollector::class),
            ]);
    }

    private function decorateHttpClient(ContainerBuilder $container): void
    {
        if (!$container->has(ClientInterface::class) || !$container->has(HttpClientCollector::class)) {
            return;
        }

        $container->register(HttpClientInterfaceProxy::class, HttpClientInterfaceProxy::class)
            ->setDecoratedService(ClientInterface::class)
            ->setArguments([
                new Reference(HttpClientInterfaceProxy::class . '.inner'),
                new Reference(HttpClientCollector::class),
            ]);
    }
}
