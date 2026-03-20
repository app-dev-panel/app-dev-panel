<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\DependencyInjection;

use AppDevPanel\Adapter\Symfony\Inspector\DoctrineSchemaProvider;
use AppDevPanel\Adapter\Symfony\Proxy\SymfonyEventDispatcherProxy;
use AppDevPanel\Api\Inspector\Controller\InspectController;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
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
        $this->upgradeSchemaProvider($container);
        $this->collectContainerParameters($container);
    }

    private function registerDebugger(ContainerBuilder $container): void
    {
        $collectorRefs = [];
        foreach ($container->findTaggedServiceIds('app_dev_panel.collector') as $id => $tags) {
            $collectorRefs[] = new Reference($id);
        }

        $container
            ->register(Debugger::class, Debugger::class)
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

        // Symfony registers the logger as 'logger' service ID.
        // MonologBundle may also alias Psr\Log\LoggerInterface → monolog.logger.
        // We decorate whichever is available: prefer 'logger' (Symfony canonical),
        // fall back to Psr\Log\LoggerInterface (MonologBundle alias).
        $serviceId = match (true) {
            $container->has('logger') => 'logger',
            $container->has(LoggerInterface::class) => LoggerInterface::class,
            default => null,
        };

        if ($serviceId === null) {
            return;
        }

        $container
            ->register(LoggerInterfaceProxy::class, LoggerInterfaceProxy::class)
            ->setDecoratedService($serviceId)
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

        $container
            ->register(SymfonyEventDispatcherProxy::class, SymfonyEventDispatcherProxy::class)
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

        $container
            ->register(HttpClientInterfaceProxy::class, HttpClientInterfaceProxy::class)
            ->setDecoratedService(ClientInterface::class)
            ->setArguments([
                new Reference(HttpClientInterfaceProxy::class . '.inner'),
                new Reference(HttpClientCollector::class),
            ]);
    }

    /**
     * Upgrades NullSchemaProvider to DoctrineSchemaProvider when Doctrine DBAL is available.
     *
     * This runs in the compiler pass (after all extensions) so doctrine.dbal.default_connection
     * is guaranteed to be registered if DoctrineBundle is active.
     */
    private function upgradeSchemaProvider(ContainerBuilder $container): void
    {
        if (!class_exists(\Doctrine\DBAL\Connection::class)) {
            return;
        }

        if (!$container->has('doctrine.dbal.default_connection')) {
            return;
        }

        $container
            ->register(SchemaProviderInterface::class, DoctrineSchemaProvider::class)
            ->setArguments([new Reference('doctrine.dbal.default_connection')])
            ->setPublic(false);
    }

    /**
     * Collects all Symfony container parameters and passes them to InspectController
     * and SymfonyConfigProvider so the inspector can display them.
     */
    private function collectContainerParameters(ContainerBuilder $container): void
    {
        $parameterBag = $container->getParameterBag();
        $params = [];

        foreach ($parameterBag->all() as $key => $value) {
            // Skip internal ADP parameters
            if (str_starts_with($key, 'app_dev_panel.')) {
                continue;
            }

            $params[$key] = $value;
        }

        ksort($params);

        // Update SymfonyConfigProvider's parameter
        $container->setParameter('app_dev_panel.container_parameters', $params);

        // Pass params as 3rd argument to InspectController
        if ($container->has(InspectController::class)) {
            $definition = $container->getDefinition(InspectController::class);
            $definition->setArgument(2, $params);
        }
    }
}
