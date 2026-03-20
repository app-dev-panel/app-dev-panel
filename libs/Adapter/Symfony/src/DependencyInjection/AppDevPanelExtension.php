<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\DependencyInjection;

use AppDevPanel\Adapter\Symfony\Controller\AdpApiController;
use AppDevPanel\Adapter\Symfony\EventSubscriber\ConsoleSubscriber;
use AppDevPanel\Adapter\Symfony\EventSubscriber\CorsSubscriber;
use AppDevPanel\Adapter\Symfony\EventSubscriber\HttpSubscriber;
use AppDevPanel\Adapter\Symfony\Inspector\DoctrineSchemaProvider;
use AppDevPanel\Adapter\Symfony\Inspector\NullSchemaProvider;
use AppDevPanel\Adapter\Symfony\Inspector\SymfonyConfigProvider;
use AppDevPanel\Adapter\Symfony\Inspector\SymfonyRouteCollectionAdapter;
use AppDevPanel\Adapter\Symfony\Inspector\SymfonyUrlMatcherAdapter;
use AppDevPanel\Api\ApiApplication;
use AppDevPanel\Api\Debug\Controller\DebugController;
use AppDevPanel\Api\Debug\Middleware\ResponseDataWrapper;
use AppDevPanel\Api\Debug\Middleware\TokenAuthMiddleware;
use AppDevPanel\Api\Debug\Repository\CollectorRepository;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Http\JsonResponseFactory;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Ingestion\Controller\IngestionController;
use AppDevPanel\Api\Inspector\Controller\CacheController as InspectorCacheController;
use AppDevPanel\Api\Inspector\Controller\CommandController;
use AppDevPanel\Api\Inspector\Controller\ComposerController;
use AppDevPanel\Api\Inspector\Controller\DatabaseController;
use AppDevPanel\Api\Inspector\Controller\FileController;
use AppDevPanel\Api\Inspector\Controller\GitController;
use AppDevPanel\Api\Inspector\Controller\GitRepositoryProvider;
use AppDevPanel\Api\Inspector\Controller\InspectController;
use AppDevPanel\Api\Inspector\Controller\OpcacheController;
use AppDevPanel\Api\Inspector\Controller\RequestController;
use AppDevPanel\Api\Inspector\Controller\RoutingController;
use AppDevPanel\Api\Inspector\Controller\ServiceController;
use AppDevPanel\Api\Inspector\Controller\TranslationController;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use AppDevPanel\Api\Inspector\Middleware\InspectorProxyMiddleware;
use AppDevPanel\Api\Middleware\IpFilterMiddleware;
use AppDevPanel\Api\PathResolver;
use AppDevPanel\Api\PathResolverInterface;
use AppDevPanel\Cli\Command\DebugQueryCommand;
use AppDevPanel\Cli\Command\DebugResetCommand;
use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\EnvironmentCollector;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\RouterCollector;
use AppDevPanel\Kernel\Collector\SecurityCollector;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector;
use AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector;
use AppDevPanel\Kernel\Collector\TemplateCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\ValidatorCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Service\FileServiceRegistry;
use AppDevPanel\Kernel\Service\ServiceRegistryInterface;
use AppDevPanel\Kernel\Storage\FileStorage;
use AppDevPanel\Kernel\Storage\StorageInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

final class AppDevPanelExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (!$config['enabled']) {
            return;
        }

        $container->setParameter('app_dev_panel.enabled', true);
        $container->setParameter('app_dev_panel.storage.path', $config['storage']['path']);
        $container->setParameter('app_dev_panel.storage.history_size', $config['storage']['history_size']);
        $container->setParameter('app_dev_panel.ignored_requests', $config['ignored_requests']);
        $container->setParameter('app_dev_panel.ignored_commands', $config['ignored_commands']);
        $container->setParameter('app_dev_panel.dumper.excluded_classes', $config['dumper']['excluded_classes']);

        $this->registerCoreServices($container, $config);
        $this->registerCollectors($container, $config);
        $this->registerEventSubscribers($container);
        $this->registerApiServices($container, $config);
        $this->registerCliCommands($container);
    }

    private function registerCoreServices(ContainerBuilder $container, array $config): void
    {
        $container->register(DebuggerIdGenerator::class, DebuggerIdGenerator::class)->setPublic(false);

        $container
            ->register(StorageInterface::class, FileStorage::class)
            ->setArguments([
                '%app_dev_panel.storage.path%',
                new Reference(DebuggerIdGenerator::class),
                '%app_dev_panel.dumper.excluded_classes%',
            ])
            ->setPublic(false);

        $container
            ->register(TimelineCollector::class, TimelineCollector::class)
            ->setPublic(false)
            ->addTag('app_dev_panel.collector');
    }

    private function registerCollectors(ContainerBuilder $container, array $config): void
    {
        $collectors = $config['collectors'];

        if ($collectors['environment'] ?? true) {
            $container
                ->register(EnvironmentCollector::class, EnvironmentCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['request']) {
            $container
                ->register(RequestCollector::class, RequestCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector')
                ->addTag('app_dev_panel.collector.web');

            $container
                ->register(WebAppInfoCollector::class, WebAppInfoCollector::class)
                ->setArguments([new Reference(TimelineCollector::class), 'Symfony'])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector')
                ->addTag('app_dev_panel.collector.web');
        }

        if ($collectors['exception']) {
            $container
                ->register(ExceptionCollector::class, ExceptionCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['log']) {
            $container
                ->register(LogCollector::class, LogCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['event']) {
            $container
                ->register(EventCollector::class, EventCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['service']) {
            $container
                ->register(ServiceCollector::class, ServiceCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['http_client']) {
            $container
                ->register(HttpClientCollector::class, HttpClientCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['var_dumper']) {
            $container
                ->register(VarDumperCollector::class, VarDumperCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['filesystem_stream']) {
            $container
                ->register(FilesystemStreamCollector::class, FilesystemStreamCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['http_stream']) {
            $container
                ->register(HttpStreamCollector::class, HttpStreamCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['command']) {
            $container
                ->register(CommandCollector::class, CommandCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector')
                ->addTag('app_dev_panel.collector.console');

            $container
                ->register(ConsoleAppInfoCollector::class, ConsoleAppInfoCollector::class)
                ->setArguments([new Reference(TimelineCollector::class), 'Symfony'])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector')
                ->addTag('app_dev_panel.collector.console');
        }

        // Symfony-specific collectors
        if ($collectors['doctrine']) {
            $container
                ->register(DatabaseCollector::class, DatabaseCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['twig']) {
            $container
                ->register(TemplateCollector::class, TemplateCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['security']) {
            $container
                ->register(SecurityCollector::class, SecurityCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['cache']) {
            $container
                ->register(CacheCollector::class, CacheCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['mailer']) {
            $container
                ->register(MailerCollector::class, MailerCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['messenger']) {
            $container
                ->register(QueueCollector::class, QueueCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['validator']) {
            $container
                ->register(ValidatorCollector::class, ValidatorCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['router']) {
            $container
                ->register(RouterCollector::class, RouterCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }
    }

    private function registerEventSubscribers(ContainerBuilder $container): void
    {
        $container
            ->register(CorsSubscriber::class, CorsSubscriber::class)
            ->addTag('kernel.event_subscriber')
            ->setPublic(false);

        $container
            ->register(HttpSubscriber::class, HttpSubscriber::class)
            ->setArguments([
                new Reference(Debugger::class),
                new Reference(RequestCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference(WebAppInfoCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference(ExceptionCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference(VarDumperCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference(EnvironmentCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
            ])
            ->addTag('kernel.event_subscriber')
            ->setPublic(false);

        $container
            ->register(ConsoleSubscriber::class, ConsoleSubscriber::class)
            ->setArguments([
                new Reference(Debugger::class),
                new Reference(CommandCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference(ConsoleAppInfoCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference(ExceptionCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference(EnvironmentCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
            ])
            ->addTag('kernel.event_subscriber')
            ->setPublic(false);
    }

    private function registerApiServices(ContainerBuilder $container, array $config): void
    {
        if (!($config['api']['enabled'] ?? true)) {
            return;
        }

        $container->setParameter(
            'app_dev_panel.api.allowed_ips',
            $config['api']['allowed_ips'] ?? ['127.0.0.1', '::1'],
        );
        $container->setParameter('app_dev_panel.api.auth_token', $config['api']['auth_token'] ?? '');
        $container->setParameter('app_dev_panel.container_parameters', []);
        $container->setParameter('app_dev_panel.bundle_config', []);

        // PSR-17 factories (use GuzzleHttp\Psr7\HttpFactory as default)
        if (!$container->has(ResponseFactoryInterface::class)) {
            $container->register(ResponseFactoryInterface::class, HttpFactory::class)->setPublic(false);
        }
        if (!$container->has(StreamFactoryInterface::class)) {
            $container->register(StreamFactoryInterface::class, HttpFactory::class)->setPublic(false);
        }
        if (!$container->has(UriFactoryInterface::class)) {
            $container->register(UriFactoryInterface::class, HttpFactory::class)->setPublic(false);
        }
        if (!$container->has(ClientInterface::class)) {
            $container
                ->register(ClientInterface::class, Client::class)
                ->setArguments([['timeout' => 10]])
                ->setPublic(false);
        }

        // Path resolver
        $container
            ->register(PathResolverInterface::class, PathResolver::class)
            ->setArguments(['%kernel.project_dir%', '%kernel.project_dir%/var'])
            ->setPublic(false);

        // JSON response factory
        $container
            ->register(JsonResponseFactoryInterface::class, JsonResponseFactory::class)
            ->setArguments([
                new Reference(ResponseFactoryInterface::class),
                new Reference(StreamFactoryInterface::class),
            ])
            ->setPublic(false);

        // Service registry
        $container
            ->register(ServiceRegistryInterface::class, FileServiceRegistry::class)
            ->setArguments(['%app_dev_panel.storage.path%/services'])
            ->setPublic(false);

        // Collector repository
        $container
            ->register(CollectorRepositoryInterface::class, CollectorRepository::class)
            ->setArguments([new Reference(StorageInterface::class)])
            ->setPublic(false);

        // Middleware — must be public so ApiApplication::buildPipeline() can fetch them via container->has/get
        $container
            ->register(IpFilterMiddleware::class, IpFilterMiddleware::class)
            ->setArguments([
                new Reference(ResponseFactoryInterface::class),
                new Reference(StreamFactoryInterface::class),
                '%app_dev_panel.api.allowed_ips%',
            ])
            ->setPublic(true);

        $container
            ->register(TokenAuthMiddleware::class, TokenAuthMiddleware::class)
            ->setArguments([
                new Reference(ResponseFactoryInterface::class),
                new Reference(StreamFactoryInterface::class),
                '%app_dev_panel.api.auth_token%',
            ])
            ->setPublic(true);

        $container
            ->register(ResponseDataWrapper::class, ResponseDataWrapper::class)
            ->setArguments([new Reference(JsonResponseFactoryInterface::class)])
            ->setPublic(true);

        $container
            ->register(InspectorProxyMiddleware::class, InspectorProxyMiddleware::class)
            ->setArguments([
                new Reference(ServiceRegistryInterface::class),
                new Reference(ClientInterface::class),
                new Reference(ResponseFactoryInterface::class),
                new Reference(StreamFactoryInterface::class),
                new Reference(UriFactoryInterface::class),
            ])
            ->setPublic(true);

        // Controllers
        $container
            ->register(DebugController::class, DebugController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(CollectorRepositoryInterface::class),
                new Reference(StorageInterface::class),
                new Reference(ResponseFactoryInterface::class),
            ])
            ->setPublic(true);

        $container
            ->register(IngestionController::class, IngestionController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(StorageInterface::class),
            ])
            ->setPublic(true);

        $container
            ->register(ServiceController::class, ServiceController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(ServiceRegistryInterface::class),
            ])
            ->setPublic(true);

        $container
            ->register(FileController::class, FileController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(PathResolverInterface::class),
            ])
            ->setPublic(true);

        $container
            ->register(GitRepositoryProvider::class, GitRepositoryProvider::class)
            ->setArguments([new Reference(PathResolverInterface::class)])
            ->setPublic(false);

        $container
            ->register(GitController::class, GitController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(GitRepositoryProvider::class),
            ])
            ->setPublic(true);

        $container
            ->register(ComposerController::class, ComposerController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(PathResolverInterface::class),
            ])
            ->setPublic(true);

        $container
            ->register(OpcacheController::class, OpcacheController::class)
            ->setArguments([new Reference(JsonResponseFactoryInterface::class)])
            ->setPublic(true);

        // Database inspector: register NullSchemaProvider as default.
        // CompilerPass will upgrade to DoctrineSchemaProvider if Doctrine DBAL is available.
        if (!$container->has(SchemaProviderInterface::class)) {
            $container->register(SchemaProviderInterface::class, NullSchemaProvider::class)->setPublic(false);
        }

        $container
            ->register(DatabaseController::class, DatabaseController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(SchemaProviderInterface::class),
            ])
            ->setPublic(true);

        // Symfony config provider for inspector
        $container
            ->register(SymfonyConfigProvider::class, SymfonyConfigProvider::class)
            ->setArguments([
                new Reference('service_container'),
                '%app_dev_panel.container_parameters%',
                '%app_dev_panel.bundle_config%',
            ])
            ->setPublic(false);

        // Register as 'config' alias so InspectController can find it
        $container->setAlias('config', SymfonyConfigProvider::class)->setPublic(true);

        $container
            ->register(InspectController::class, InspectController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference('service_container'),
            ])
            ->setPublic(true);

        $container
            ->register(InspectorCacheController::class, InspectorCacheController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference('service_container'),
            ])
            ->setPublic(true);

        $container
            ->register(TranslationController::class, TranslationController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference('logger', ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference('service_container'),
            ])
            ->setPublic(true);

        $container
            ->register(CommandController::class, CommandController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(PathResolverInterface::class),
                new Reference('service_container'),
            ])
            ->setPublic(true);

        // Symfony route inspection adapters (null-safe when 'router' service is absent)
        $container
            ->register(SymfonyRouteCollectionAdapter::class, SymfonyRouteCollectionAdapter::class)
            ->setArguments([new Reference('router', ContainerBuilder::IGNORE_ON_INVALID_REFERENCE)])
            ->setPublic(false);

        $container
            ->register(SymfonyUrlMatcherAdapter::class, SymfonyUrlMatcherAdapter::class)
            ->setArguments([new Reference('router', ContainerBuilder::IGNORE_ON_INVALID_REFERENCE)])
            ->setPublic(false);

        $container
            ->register(RoutingController::class, RoutingController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(SymfonyRouteCollectionAdapter::class, ContainerBuilder::IGNORE_ON_INVALID_REFERENCE),
                new Reference(SymfonyUrlMatcherAdapter::class, ContainerBuilder::IGNORE_ON_INVALID_REFERENCE),
            ])
            ->setPublic(true);

        $container
            ->register(RequestController::class, RequestController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(CollectorRepositoryInterface::class),
            ])
            ->setPublic(true);

        // ApiApplication
        $container
            ->register(ApiApplication::class, ApiApplication::class)
            ->setArguments([
                new Reference('service_container'),
                new Reference(ResponseFactoryInterface::class),
                new Reference(StreamFactoryInterface::class),
            ])
            ->setPublic(true);

        // Bridge controller
        $container
            ->register(AdpApiController::class, AdpApiController::class)
            ->setArguments([new Reference(ApiApplication::class)])
            ->addTag('controller.service_arguments')
            ->setPublic(true);
    }

    private function registerCliCommands(ContainerBuilder $container): void
    {
        $container
            ->register(DebugResetCommand::class, DebugResetCommand::class)
            ->setArguments([
                new Reference(StorageInterface::class),
                new Reference(Debugger::class),
            ])
            ->addTag('console.command')
            ->setPublic(false);

        $container
            ->register(DebugQueryCommand::class, DebugQueryCommand::class)
            ->setArguments([
                new Reference(CollectorRepositoryInterface::class),
            ])
            ->addTag('console.command')
            ->setPublic(false);
    }

    public function getAlias(): string
    {
        return 'app_dev_panel';
    }
}
