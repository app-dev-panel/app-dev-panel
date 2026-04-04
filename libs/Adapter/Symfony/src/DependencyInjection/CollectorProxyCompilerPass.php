<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\DependencyInjection;

use AppDevPanel\Adapter\Symfony\Inspector\DoctrineSchemaProvider;
use AppDevPanel\Adapter\Symfony\Proxy\DoctrineDbalMiddleware;
use AppDevPanel\Adapter\Symfony\Proxy\SymfonyCacheProxy;
use AppDevPanel\Adapter\Symfony\Proxy\SymfonyEventDispatcherProxy;
use AppDevPanel\Adapter\Symfony\Proxy\SymfonyTranslatorProxy;
use AppDevPanel\Adapter\Symfony\Proxy\SymfonyValidatorProxy;
use AppDevPanel\Adapter\Symfony\Proxy\TwigEnvironmentProxy;
use AppDevPanel\Api\Inspector\Controller\InspectController;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\LoggerInterfaceProxy;
use AppDevPanel\Kernel\Collector\OpenTelemetryCollector;
use AppDevPanel\Kernel\Collector\SpanProcessorInterfaceProxy;
use AppDevPanel\Kernel\Collector\TemplateCollector;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use AppDevPanel\Kernel\Collector\ValidatorCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\DebuggerIgnoreConfig;
use AppDevPanel\Kernel\DebugServer\LoggerDecorator;
use AppDevPanel\Kernel\Storage\StorageInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        $this->decorateTranslator($container);
        $this->decorateSpanProcessor($container);
        $this->decorateTwig($container);
        $this->decorateDoctrineDbal($container);
        $this->decorateValidator($container);
        $this->decorateCache($container);
        $this->upgradeSchemaProvider($container);
        $this->collectContainerParameters($container);
    }

    private function registerDebugger(ContainerBuilder $container): void
    {
        $collectorRefs = [];
        foreach ($container->findTaggedServiceIds('app_dev_panel.collector') as $id => $tags) {
            $collectorRefs[] = new Reference($id);
        }

        $ignoreConfigDef = new Definition(DebuggerIgnoreConfig::class, [
            '%app_dev_panel.ignored_requests%',
            '%app_dev_panel.ignored_commands%',
        ]);

        $container
            ->register(Debugger::class, Debugger::class)
            ->setArguments([
                new Reference(DebuggerIdGenerator::class),
                new Reference(StorageInterface::class),
                $collectorRefs,
                $ignoreConfigDef,
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

        // Wrap inner logger with LoggerDecorator for Live Feed broadcasting,
        // then wrap that with LoggerInterfaceProxy for collector capture.
        $container
            ->register(LoggerDecorator::class, LoggerDecorator::class)
            ->setArguments([new Reference(LoggerInterfaceProxy::class . '.inner')])
            ->setPublic(false);

        $container
            ->register(LoggerInterfaceProxy::class, LoggerInterfaceProxy::class)
            ->setDecoratedService($serviceId)
            ->setArguments([
                new Reference(LoggerDecorator::class),
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

    private function decorateTranslator(ContainerBuilder $container): void
    {
        if (!$container->has(TranslatorCollector::class)) {
            return;
        }

        // Symfony registers the translator as 'translator' service ID.
        // Decorate whichever is available: prefer 'translator' (Symfony canonical),
        // fall back to TranslatorInterface FQCN.
        $serviceId = match (true) {
            $container->has('translator') => 'translator',
            $container->has(TranslatorInterface::class) => TranslatorInterface::class,
            default => null,
        };

        if ($serviceId === null) {
            return;
        }

        $container
            ->register(SymfonyTranslatorProxy::class, SymfonyTranslatorProxy::class)
            ->setDecoratedService($serviceId)
            ->setArguments([
                new Reference(SymfonyTranslatorProxy::class . '.inner'),
                new Reference(TranslatorCollector::class),
            ]);
    }

    private function decorateSpanProcessor(ContainerBuilder $container): void
    {
        if (!interface_exists(\OpenTelemetry\SDK\Trace\SpanProcessorInterface::class)) {
            return;
        }

        if (!$container->has(OpenTelemetryCollector::class)) {
            return;
        }

        if (!$container->has(\OpenTelemetry\SDK\Trace\SpanProcessorInterface::class)) {
            return;
        }

        $container
            ->register(SpanProcessorInterfaceProxy::class, SpanProcessorInterfaceProxy::class)
            ->setDecoratedService(\OpenTelemetry\SDK\Trace\SpanProcessorInterface::class)
            ->setArguments([
                new Reference(SpanProcessorInterfaceProxy::class . '.inner'),
                new Reference(OpenTelemetryCollector::class),
            ]);
    }

    private function decorateTwig(ContainerBuilder $container): void
    {
        if (!$container->has(TemplateCollector::class)) {
            return;
        }

        if (!$container->has('twig')) {
            return;
        }

        $container
            ->register(TwigEnvironmentProxy::class, TwigEnvironmentProxy::class)
            ->setDecoratedService('twig')
            ->setArguments([
                new Reference(TwigEnvironmentProxy::class . '.inner'),
                new Reference(TemplateCollector::class),
            ]);
    }

    private function decorateDoctrineDbal(ContainerBuilder $container): void
    {
        if (!$container->has(DatabaseCollector::class)) {
            return;
        }

        if (!interface_exists(\Doctrine\DBAL\Driver\Middleware::class)) {
            return;
        }

        if (!$container->has('doctrine.dbal.default_connection')) {
            return;
        }

        // Register the middleware with the doctrine.middleware tag.
        // DoctrineBundle's MiddlewaresPass picks up this tag and wires it
        // into all (or specified) DBAL connections.
        $container
            ->register(DoctrineDbalMiddleware::class, DoctrineDbalMiddleware::class)
            ->setArguments([new Reference(DatabaseCollector::class)])
            ->addTag('doctrine.middleware')
            ->setPublic(false);
    }

    private function decorateValidator(ContainerBuilder $container): void
    {
        if (!$container->has(ValidatorCollector::class)) {
            return;
        }

        if (!interface_exists(\Symfony\Component\Validator\Validator\ValidatorInterface::class)) {
            return;
        }

        $serviceId = match (true) {
            $container->has('validator') => 'validator',
            $container->has(\Symfony\Component\Validator\Validator\ValidatorInterface::class)
                => \Symfony\Component\Validator\Validator\ValidatorInterface::class,
            default => null,
        };

        if ($serviceId === null) {
            return;
        }

        $container
            ->register(SymfonyValidatorProxy::class, SymfonyValidatorProxy::class)
            ->setDecoratedService($serviceId)
            ->setArguments([
                new Reference(SymfonyValidatorProxy::class . '.inner'),
                new Reference(ValidatorCollector::class),
            ]);
    }

    private function decorateCache(ContainerBuilder $container): void
    {
        if (!$container->has(CacheCollector::class)) {
            return;
        }

        // Decorate the default cache pool ('cache.app' is Symfony's default app cache)
        if ($container->has('cache.app')) {
            $container
                ->register('app_dev_panel.cache.app.proxy', SymfonyCacheProxy::class)
                ->setDecoratedService('cache.app')
                ->setArguments([
                    new Reference('app_dev_panel.cache.app.proxy.inner'),
                    new Reference(CacheCollector::class),
                    'app',
                ]);
        }
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
