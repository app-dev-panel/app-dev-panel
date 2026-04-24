<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\DependencyInjection;

use AppDevPanel\Adapter\Symfony\Collector\RouterDataExtractor;
use AppDevPanel\Adapter\Symfony\Controller\AdpApiController;
use AppDevPanel\Adapter\Symfony\Controller\AdpAssetController;
use AppDevPanel\Adapter\Symfony\EventSubscriber\AssetMapperSubscriber;
use AppDevPanel\Adapter\Symfony\EventSubscriber\AuthorizationSubscriber;
use AppDevPanel\Adapter\Symfony\EventSubscriber\ConsoleSubscriber;
use AppDevPanel\Adapter\Symfony\EventSubscriber\CorsSubscriber;
use AppDevPanel\Adapter\Symfony\EventSubscriber\HttpSubscriber;
use AppDevPanel\Adapter\Symfony\EventSubscriber\HttpSubscriberCollectors;
use AppDevPanel\Adapter\Symfony\EventSubscriber\MailerSubscriber;
use AppDevPanel\Adapter\Symfony\Inspector\NullSchemaProvider;
use AppDevPanel\Adapter\Symfony\Inspector\SymfonyConfigProvider;
use AppDevPanel\Adapter\Symfony\Inspector\SymfonyRouteCollectionAdapter;
use AppDevPanel\Adapter\Symfony\Inspector\SymfonyUrlMatcherAdapter;
use AppDevPanel\Adapter\Symfony\Proxy\MessengerCollectorMiddleware;
use AppDevPanel\Api\ApiApplication;
use AppDevPanel\Api\Debug\Controller\DebugController;
use AppDevPanel\Api\Debug\Controller\SettingsController;
use AppDevPanel\Api\Debug\Middleware\ResponseDataWrapper;
use AppDevPanel\Api\Debug\Middleware\TokenAuthMiddleware;
use AppDevPanel\Api\Debug\Repository\CollectorRepository;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Http\JsonResponseFactory;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Ingestion\Controller\IngestionController;
use AppDevPanel\Api\Inspector\Authorization\AuthorizationConfigProviderInterface;
use AppDevPanel\Api\Inspector\Authorization\NullAuthorizationConfigProvider;
use AppDevPanel\Api\Inspector\Controller\AuthorizationController;
use AppDevPanel\Api\Inspector\Controller\CacheController as InspectorCacheController;
use AppDevPanel\Api\Inspector\Controller\CodeCoverageController;
use AppDevPanel\Api\Inspector\Controller\CommandController;
use AppDevPanel\Api\Inspector\Controller\ComposerController;
use AppDevPanel\Api\Inspector\Controller\DatabaseController;
use AppDevPanel\Api\Inspector\Controller\ElasticsearchController;
use AppDevPanel\Api\Inspector\Controller\FileController;
use AppDevPanel\Api\Inspector\Controller\GitController;
use AppDevPanel\Api\Inspector\Controller\GitRepositoryProvider;
use AppDevPanel\Api\Inspector\Controller\HttpMockController;
use AppDevPanel\Api\Inspector\Controller\InspectController;
use AppDevPanel\Api\Inspector\Controller\OpcacheController;
use AppDevPanel\Api\Inspector\Controller\RequestController;
use AppDevPanel\Api\Inspector\Controller\RoutingController;
use AppDevPanel\Api\Inspector\Controller\ServiceController;
use AppDevPanel\Api\Inspector\Controller\TranslationController;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use AppDevPanel\Api\Inspector\Elasticsearch\ElasticsearchProviderInterface;
use AppDevPanel\Api\Inspector\Elasticsearch\NullElasticsearchProvider;
use AppDevPanel\Api\Inspector\HttpMock\HttpMockProviderInterface;
use AppDevPanel\Api\Inspector\HttpMock\NullHttpMockProvider;
use AppDevPanel\Api\Inspector\Middleware\InspectorProxyMiddleware;
use AppDevPanel\Api\Llm\Acp\AcpCommandVerifier;
use AppDevPanel\Api\Llm\Acp\AcpCommandVerifierInterface;
use AppDevPanel\Api\Llm\Acp\AcpDaemonManager;
use AppDevPanel\Api\Llm\Acp\AcpDaemonManagerInterface;
use AppDevPanel\Api\Llm\Controller\LlmController;
use AppDevPanel\Api\Llm\FileLlmHistoryStorage;
use AppDevPanel\Api\Llm\FileLlmSettings;
use AppDevPanel\Api\Llm\LlmHistoryStorageInterface;
use AppDevPanel\Api\Llm\LlmProviderService;
use AppDevPanel\Api\Llm\LlmSettingsInterface;
use AppDevPanel\Api\Mcp\Controller\McpController;
use AppDevPanel\Api\Mcp\Controller\McpSettingsController;
use AppDevPanel\Api\Mcp\McpSettings;
use AppDevPanel\Api\Middleware\IpFilterMiddleware;
use AppDevPanel\Api\NullPathMapper;
use AppDevPanel\Api\Panel\PanelConfig;
use AppDevPanel\Api\Panel\PanelController;
use AppDevPanel\Api\PathMapper;
use AppDevPanel\Api\PathMapperInterface;
use AppDevPanel\Api\PathResolver;
use AppDevPanel\Api\PathResolverInterface;
use AppDevPanel\Api\Toolbar\ToolbarConfig;
use AppDevPanel\Api\Toolbar\ToolbarInjector;
use AppDevPanel\Cli\Command\DebugDumpCommand;
use AppDevPanel\Cli\Command\DebugQueryCommand;
use AppDevPanel\Cli\Command\DebugResetCommand;
use AppDevPanel\Cli\Command\DebugServerBroadcastCommand;
use AppDevPanel\Cli\Command\DebugServerCommand;
use AppDevPanel\Cli\Command\DebugSummaryCommand;
use AppDevPanel\Cli\Command\DebugTailCommand;
use AppDevPanel\Cli\Command\FrontendUpdateCommand;
use AppDevPanel\Cli\Command\InspectConfigCommand;
use AppDevPanel\Cli\Command\InspectDatabaseCommand;
use AppDevPanel\Cli\Command\InspectRoutesCommand;
use AppDevPanel\Kernel\Collector\AssetBundleCollector;
use AppDevPanel\Kernel\Collector\AuthorizationCollector;
use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CodeCoverageCollector;
use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\DeprecationCollector;
use AppDevPanel\Kernel\Collector\ElasticsearchCollector;
use AppDevPanel\Kernel\Collector\EnvironmentCollector;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\OpenTelemetryCollector;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\RedisCollector;
use AppDevPanel\Kernel\Collector\RouterCollector;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector;
use AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector;
use AppDevPanel\Kernel\Collector\TemplateCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use AppDevPanel\Kernel\Collector\ValidatorCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Service\FileServiceRegistry;
use AppDevPanel\Kernel\Service\ServiceRegistryInterface;
use AppDevPanel\Kernel\Storage\BroadcastingStorage;
use AppDevPanel\Kernel\Storage\StorageFactory;
use AppDevPanel\Kernel\Storage\StorageInterface;
use AppDevPanel\McpServer\Inspector\InspectorClient;
use AppDevPanel\McpServer\McpServer;
use AppDevPanel\McpServer\McpToolRegistryFactory;
use AppDevPanel\McpServer\Tool\ToolRegistry;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
        $container->setParameter('app_dev_panel.storage.driver', $config['storage']['driver']);
        $container->setParameter('app_dev_panel.storage.history_size', $config['storage']['history_size']);
        $container->setParameter('app_dev_panel.ignored_requests', $config['ignored_requests']);
        $container->setParameter('app_dev_panel.ignored_commands', $config['ignored_commands']);
        $container->setParameter('app_dev_panel.dumper.excluded_classes', $config['dumper']['excluded_classes']);
        $container->setParameter('app_dev_panel.path_mapping', $config['path_mapping'] ?? []);

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
            ->register('app_dev_panel.storage.file', StorageInterface::class)
            ->setFactory([StorageFactory::class, 'create'])
            ->setArguments([
                '%app_dev_panel.storage.driver%',
                '%app_dev_panel.storage.path%',
                new Reference(DebuggerIdGenerator::class),
                '%app_dev_panel.dumper.excluded_classes%',
            ])
            ->setPublic(false);

        $container
            ->register(StorageInterface::class, BroadcastingStorage::class)
            ->setArguments([new Reference('app_dev_panel.storage.file')])
            ->setPublic(false);

        $container
            ->register(TimelineCollector::class, TimelineCollector::class)
            ->setPublic(false)
            ->addTag('app_dev_panel.collector');
    }

    private function registerCollectors(ContainerBuilder $container, array $config): void
    {
        $collectors = $config['collectors'];

        $this->registerKernelCollectors($container, $collectors);
        $this->registerSymfonySpecificCollectors($container, $collectors);
    }

    private function registerKernelCollectors(ContainerBuilder $container, array $collectors): void
    {
        if ($collectors['environment'] ?? true) {
            $container
                ->register(EnvironmentCollector::class, EnvironmentCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['request']) {
            $this->registerRequestCollectors($container);
        }

        $this->registerSimpleCollectors($container, $collectors);
        $this->registerStreamCollectors($container, $collectors);

        if ($collectors['code_coverage']) {
            $container
                ->register(CodeCoverageCollector::class, CodeCoverageCollector::class)
                ->setArguments([new Reference(TimelineCollector::class), [], ['vendor']])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['command']) {
            $this->registerCommandCollectors($container);
        }
    }

    private function registerRequestCollectors(ContainerBuilder $container): void
    {
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

    private function registerSimpleCollectors(ContainerBuilder $container, array $collectors): void
    {
        /** @var array<string, class-string> $timelineCollectorMap */
        $timelineCollectorMap = [
            'exception' => ExceptionCollector::class,
            'log' => LogCollector::class,
            'event' => EventCollector::class,
            'service' => ServiceCollector::class,
            'http_client' => HttpClientCollector::class,
            'var_dumper' => VarDumperCollector::class,
            'deprecation' => DeprecationCollector::class,
            'opentelemetry' => OpenTelemetryCollector::class,
            'elasticsearch' => ElasticsearchCollector::class,
            'redis' => RedisCollector::class,
        ];

        foreach ($timelineCollectorMap as $key => $class) {
            if (!$collectors[$key]) {
                continue;
            }
            $container
                ->register($class, $class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }
    }

    private function registerStreamCollectors(ContainerBuilder $container, array $collectors): void
    {
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
    }

    private function registerCommandCollectors(ContainerBuilder $container): void
    {
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

    private function registerSymfonySpecificCollectors(ContainerBuilder $container, array $collectors): void
    {
        /** @var array<string, class-string> $timelineCollectorMap */
        $timelineCollectorMap = [
            'doctrine' => DatabaseCollector::class,
            'twig' => TemplateCollector::class,
            'cache' => CacheCollector::class,
            'mailer' => MailerCollector::class,
            'queue' => QueueCollector::class,
        ];

        foreach ($timelineCollectorMap as $key => $class) {
            if (!$collectors[$key]) {
                continue;
            }
            $container
                ->register($class, $class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        /** @var array<string, class-string> $simpleCollectorMap */
        $simpleCollectorMap = [
            'security' => AuthorizationCollector::class,
            'validator' => ValidatorCollector::class,
            'translator' => TranslatorCollector::class,
        ];

        foreach ($simpleCollectorMap as $key => $class) {
            if (!$collectors[$key]) {
                continue;
            }
            $container->register($class, $class)->setPublic(false)->addTag('app_dev_panel.collector');
        }

        if ($collectors['router']) {
            $this->registerRouterCollector($container);
        }

        if ($collectors['assets']) {
            $container
                ->register(AssetBundleCollector::class, AssetBundleCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }
    }

    private function registerRouterCollector(ContainerBuilder $container): void
    {
        $container
            ->register(RouterCollector::class, RouterCollector::class)
            ->setPublic(false)
            ->addTag('app_dev_panel.collector');

        $container
            ->register(RouterDataExtractor::class, RouterDataExtractor::class)
            ->setArguments([
                new Reference(RouterCollector::class),
                new Reference('router', ContainerBuilder::IGNORE_ON_INVALID_REFERENCE),
            ])
            ->setPublic(false);
    }

    private function registerEventSubscribers(ContainerBuilder $container): void
    {
        $container
            ->register(CorsSubscriber::class, CorsSubscriber::class)
            ->addTag('kernel.event_subscriber')
            ->setPublic(false);

        $container
            ->register(HttpSubscriberCollectors::class, HttpSubscriberCollectors::class)
            ->setArguments([
                new Reference(RequestCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference(WebAppInfoCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference(ExceptionCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference(VarDumperCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference(EnvironmentCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference(RouterDataExtractor::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
            ])
            ->setPublic(false);

        $container
            ->register(HttpSubscriber::class, HttpSubscriber::class)
            ->setArguments([
                new Reference(Debugger::class),
                new Reference(HttpSubscriberCollectors::class),
                new Reference(ToolbarInjector::class),
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

        // Asset mapper subscriber — only when symfony/asset-mapper is available and service is registered
        if (
            $container->has(AssetBundleCollector::class)
            && interface_exists(\Symfony\Component\AssetMapper\AssetMapperInterface::class)
            && $container->has(\Symfony\Component\AssetMapper\AssetMapperInterface::class)
        ) {
            $container
                ->register(AssetMapperSubscriber::class, AssetMapperSubscriber::class)
                ->setArguments([
                    new Reference(AssetBundleCollector::class),
                    new Reference(\Symfony\Component\AssetMapper\AssetMapperInterface::class),
                ])
                ->addTag('kernel.event_subscriber')
                ->setPublic(false);
        }

        // Authorization subscriber — only when symfony/security-http is available and collector is enabled
        if (
            $container->has(AuthorizationCollector::class)
            && class_exists(\Symfony\Component\Security\Http\Event\LoginSuccessEvent::class)
        ) {
            $container
                ->register(AuthorizationSubscriber::class, AuthorizationSubscriber::class)
                ->setArguments([new Reference(AuthorizationCollector::class)])
                ->addTag('kernel.event_subscriber')
                ->setPublic(false);
        }

        // Mailer subscriber — only when symfony/mailer is available and collector is enabled
        if (
            $container->has(MailerCollector::class) && class_exists(\Symfony\Component\Mailer\Event\MessageEvent::class)
        ) {
            $container
                ->register(MailerSubscriber::class, MailerSubscriber::class)
                ->setArguments([new Reference(MailerCollector::class)])
                ->addTag('kernel.event_subscriber')
                ->setPublic(false);
        }

        // Messenger middleware — only when symfony/messenger is available and collector is enabled
        if (
            $container->has(QueueCollector::class)
            && interface_exists(\Symfony\Component\Messenger\Middleware\MiddlewareInterface::class)
        ) {
            $container
                ->register(MessengerCollectorMiddleware::class, MessengerCollectorMiddleware::class)
                ->setArguments([new Reference(QueueCollector::class)])
                ->setPublic(false);
        }
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

        $this->registerPsrFactories($container);
        $this->registerApiCoreServices($container);
        $this->registerApiMiddleware($container);
        $this->registerApiControllers($container, $config);
        $this->registerInspectorServices($container);
        $this->registerApiApplication($container);
    }

    private function registerPsrFactories(ContainerBuilder $container): void
    {
        if (!$container->has(ResponseFactoryInterface::class)) {
            $container->register(ResponseFactoryInterface::class, HttpFactory::class)->setPublic(false);
        }
        if (!$container->has(StreamFactoryInterface::class)) {
            $container->register(StreamFactoryInterface::class, HttpFactory::class)->setPublic(false);
        }
        if (!$container->has(RequestFactoryInterface::class)) {
            $container->register(RequestFactoryInterface::class, HttpFactory::class)->setPublic(false);
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
    }

    private function registerApiCoreServices(ContainerBuilder $container): void
    {
        $container
            ->register(PathResolverInterface::class, PathResolver::class)
            ->setArguments(['%kernel.project_dir%', '%kernel.project_dir%/var'])
            ->setPublic(false);

        $pathMapping = $config['path_mapping'] ?? [];
        $pathMapperClass = $pathMapping !== [] ? PathMapper::class : NullPathMapper::class;
        $pathMapperArgs = $pathMapping !== [] ? [$pathMapping] : [];
        $container
            ->register(PathMapperInterface::class, $pathMapperClass)
            ->setArguments($pathMapperArgs)
            ->setPublic(false);

        $container
            ->register(JsonResponseFactoryInterface::class, JsonResponseFactory::class)
            ->setArguments([
                new Reference(ResponseFactoryInterface::class),
                new Reference(StreamFactoryInterface::class),
            ])
            ->setPublic(false);

        $container
            ->register(ServiceRegistryInterface::class, FileServiceRegistry::class)
            ->setArguments(['%app_dev_panel.storage.path%/services'])
            ->setPublic(false);

        $container
            ->register(CollectorRepositoryInterface::class, CollectorRepository::class)
            ->setArguments([new Reference(StorageInterface::class)])
            ->setPublic(false);
    }

    private function registerApiMiddleware(ContainerBuilder $container): void
    {
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
    }

    private function registerApiControllers(ContainerBuilder $container, array $config): void
    {
        $container
            ->register(DebugController::class, DebugController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(CollectorRepositoryInterface::class),
                new Reference(StorageInterface::class),
                new Reference(ResponseFactoryInterface::class),
            ])
            ->setPublic(true);

        $panelStaticUrl = $config['panel']['static_url'] ?? '';
        if ($panelStaticUrl === '') {
            $panelStaticUrl = $this->detectPanelStaticUrl();
        }
        $container->register(PanelConfig::class, PanelConfig::class)->setArguments([$panelStaticUrl])->setPublic(false);

        $toolbarEnabled = $config['toolbar']['enabled'] ?? true;
        $toolbarStaticUrl = $config['toolbar']['static_url'] ?? '';
        if ($toolbarStaticUrl === '' && $this->frontendAssetsAvailable()) {
            // Toolbar lives alongside the panel under /debug-assets/toolbar/* when we
            // serve from the FrontendAssets package.
            $toolbarStaticUrl = '/debug-assets';
        }
        $container
            ->register(ToolbarConfig::class, ToolbarConfig::class)
            ->setArguments([$toolbarEnabled, $toolbarStaticUrl])
            ->setPublic(false);
        $container
            ->register(ToolbarInjector::class, ToolbarInjector::class)
            ->setArguments([
                new Reference(PanelConfig::class),
                new Reference(ToolbarConfig::class),
            ])
            ->setPublic(false);

        $container
            ->register(PanelController::class, PanelController::class)
            ->setArguments([
                new Reference(ResponseFactoryInterface::class),
                new Reference(StreamFactoryInterface::class),
                new Reference(PanelConfig::class),
            ])
            ->setPublic(true);

        $container
            ->register(IngestionController::class, IngestionController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(StorageInterface::class),
            ])
            ->setPublic(true);

        $inspectorUrl = $config['api']['inspector_url'] ?? null;
        if (is_string($inspectorUrl) && $inspectorUrl !== '') {
            $container
                ->register(InspectorClient::class, InspectorClient::class)
                ->setArguments([$inspectorUrl])
                ->setPublic(false);
        }

        $container
            ->register(ToolRegistry::class, ToolRegistry::class)
            ->setFactory([McpToolRegistryFactory::class, 'create'])
            ->setArguments([
                new Reference(StorageInterface::class),
                new Reference(InspectorClient::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
            ])
            ->setPublic(false);

        $container
            ->register(McpSettings::class, McpSettings::class)
            ->setArguments(['%app_dev_panel.storage.path%'])
            ->setPublic(true);

        $container
            ->register(McpServer::class, McpServer::class)
            ->setArguments([new Reference(ToolRegistry::class)])
            ->setPublic(true);

        $container
            ->register(McpController::class, McpController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(McpServer::class),
                new Reference(McpSettings::class),
            ])
            ->setPublic(true);

        $container
            ->register(McpSettingsController::class, McpSettingsController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(McpSettings::class),
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
                new Reference(PathMapperInterface::class),
            ])
            ->setPublic(true);

        $container
            ->register(SettingsController::class, SettingsController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(PathMapperInterface::class),
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

        // Authorization inspector: register NullAuthorizationConfigProvider as default.
        if (!$container->has(AuthorizationConfigProviderInterface::class)) {
            $container
                ->register(AuthorizationConfigProviderInterface::class, NullAuthorizationConfigProvider::class)
                ->setPublic(false);
        }

        $container
            ->register(AuthorizationController::class, AuthorizationController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(AuthorizationConfigProviderInterface::class),
            ])
            ->setPublic(true);

        // HTTP mock inspector: register NullHttpMockProvider as default.
        if (!$container->has(HttpMockProviderInterface::class)) {
            $container->register(HttpMockProviderInterface::class, NullHttpMockProvider::class)->setPublic(false);
        }

        $container
            ->register(HttpMockController::class, HttpMockController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(HttpMockProviderInterface::class),
            ])
            ->setPublic(true);

        // Elasticsearch inspector: register NullElasticsearchProvider as default.
        if (!$container->has(ElasticsearchProviderInterface::class)) {
            $container
                ->register(ElasticsearchProviderInterface::class, NullElasticsearchProvider::class)
                ->setPublic(false);
        }

        $container
            ->register(ElasticsearchController::class, ElasticsearchController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(ElasticsearchProviderInterface::class),
            ])
            ->setPublic(true);

        $container
            ->register(CodeCoverageController::class, CodeCoverageController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(PathResolverInterface::class),
            ])
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

        $container
            ->register(RequestController::class, RequestController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(CollectorRepositoryInterface::class),
            ])
            ->setPublic(true);

        // LLM settings
        $container
            ->register(LlmSettingsInterface::class, FileLlmSettings::class)
            ->setArguments(['%app_dev_panel.storage.path%'])
            ->setPublic(true);

        // LLM history storage
        $container
            ->register(LlmHistoryStorageInterface::class, FileLlmHistoryStorage::class)
            ->setArguments(['%app_dev_panel.storage.path%'])
            ->setPublic(true);

        // ACP daemon manager and command verifier
        $container->register(AcpCommandVerifierInterface::class, AcpCommandVerifier::class)->setPublic(true);
        $container
            ->register(AcpDaemonManagerInterface::class, AcpDaemonManager::class)
            ->setArguments(['%app_dev_panel.storage.path%'])
            ->setPublic(true);

        // LLM provider service
        $container
            ->register(LlmProviderService::class, LlmProviderService::class)
            ->setArguments([
                new Reference(LlmSettingsInterface::class),
                new Reference(ClientInterface::class),
                new Reference(RequestFactoryInterface::class),
                new Reference(StreamFactoryInterface::class),
                new Reference(AcpDaemonManagerInterface::class),
            ])
            ->setPublic(true);

        // LLM controller
        $container
            ->register(LlmController::class, LlmController::class)
            ->setArguments([
                new Reference(JsonResponseFactoryInterface::class),
                new Reference(LlmSettingsInterface::class),
                new Reference(LlmProviderService::class),
                new Reference(LlmHistoryStorageInterface::class),
                new Reference(RequestFactoryInterface::class),
                new Reference(StreamFactoryInterface::class),
                new Reference(ClientInterface::class),
                new Reference(AcpCommandVerifierInterface::class),
                new Reference(AcpDaemonManagerInterface::class),
            ])
            ->setPublic(true);
    }

    private function registerInspectorServices(ContainerBuilder $container): void
    {
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
    }

    private function registerApiApplication(ContainerBuilder $container): void
    {
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

        // Static asset controller — serves panel/toolbar bundles from app-dev-panel/frontend-assets.
        $container
            ->register(AdpAssetController::class, AdpAssetController::class)
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

        $container
            ->register(DebugDumpCommand::class, DebugDumpCommand::class)
            ->setArguments([
                new Reference(CollectorRepositoryInterface::class),
            ])
            ->addTag('console.command')
            ->setPublic(false);

        $container
            ->register(DebugSummaryCommand::class, DebugSummaryCommand::class)
            ->setArguments([
                new Reference(CollectorRepositoryInterface::class),
            ])
            ->addTag('console.command')
            ->setPublic(false);

        $container
            ->register(DebugTailCommand::class, DebugTailCommand::class)
            ->setArguments([
                new Reference(CollectorRepositoryInterface::class),
            ])
            ->addTag('console.command')
            ->setPublic(false);

        $container
            ->register(InspectDatabaseCommand::class, InspectDatabaseCommand::class)
            ->setArguments([
                new Reference(SchemaProviderInterface::class),
            ])
            ->addTag('console.command')
            ->setPublic(false);

        $container
            ->register(InspectRoutesCommand::class, InspectRoutesCommand::class)
            ->setArguments([
                new Reference(SymfonyRouteCollectionAdapter::class),
                new Reference(SymfonyUrlMatcherAdapter::class),
            ])
            ->addTag('console.command')
            ->setPublic(false);

        $container
            ->register(InspectConfigCommand::class, InspectConfigCommand::class)
            ->setArguments([
                new Reference('service_container'),
                [],
            ])
            ->addTag('console.command')
            ->setPublic(false);

        $container
            ->register(FrontendUpdateCommand::class, FrontendUpdateCommand::class)
            ->addTag('console.command')
            ->setPublic(false);

        $container
            ->register(DebugServerCommand::class, DebugServerCommand::class)
            ->addTag('console.command')
            ->setPublic(false);

        $container
            ->register(DebugServerBroadcastCommand::class, DebugServerBroadcastCommand::class)
            ->addTag('console.command')
            ->setPublic(false);
    }

    public function getAlias(): string
    {
        return 'app_dev_panel';
    }

    /**
     * Resolve the panel static URL when the user has not configured one explicitly.
     * Preference order:
     * 1. `app-dev-panel/frontend-assets` installed and populated → `/debug-assets`
     *    (served by `AdpAssetController` from the package's `dist/`).
     * 2. Legacy local assets copied to the bundle's `Resources/public/` → `/bundles/appdevpanel`.
     * 3. CDN fallback (`PanelConfig::DEFAULT_STATIC_URL`).
     */
    private function detectPanelStaticUrl(): string
    {
        if ($this->frontendAssetsAvailable()) {
            return '/debug-assets';
        }

        $bundleAssetsPath = \dirname(__DIR__, 2) . '/Resources/public/bundle.js';
        if (file_exists($bundleAssetsPath)) {
            return '/bundles/appdevpanel';
        }

        return PanelConfig::DEFAULT_STATIC_URL;
    }

    private function frontendAssetsAvailable(): bool
    {
        return (
            class_exists(\AppDevPanel\FrontendAssets\FrontendAssets::class)
            && \AppDevPanel\FrontendAssets\FrontendAssets::exists()
        );
    }
}
