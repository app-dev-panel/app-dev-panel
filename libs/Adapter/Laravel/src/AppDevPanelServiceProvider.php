<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel;

use AppDevPanel\Adapter\Laravel\Collector\RouterDataExtractor;
use AppDevPanel\Adapter\Laravel\Controller\AdpApiController;
use AppDevPanel\Adapter\Laravel\EventListener\CacheListener;
use AppDevPanel\Adapter\Laravel\EventListener\ConsoleListener;
use AppDevPanel\Adapter\Laravel\EventListener\DatabaseListener;
use AppDevPanel\Adapter\Laravel\EventListener\HttpClientListener;
use AppDevPanel\Adapter\Laravel\EventListener\MailListener;
use AppDevPanel\Adapter\Laravel\EventListener\QueueListener;
use AppDevPanel\Adapter\Laravel\EventListener\SecurityListener;
use AppDevPanel\Adapter\Laravel\Inspector\LaravelConfigProvider;
use AppDevPanel\Adapter\Laravel\Inspector\LaravelRouteCollectionAdapter;
use AppDevPanel\Adapter\Laravel\Inspector\LaravelSchemaProvider;
use AppDevPanel\Adapter\Laravel\Inspector\LaravelUrlMatcherAdapter;
use AppDevPanel\Adapter\Laravel\Inspector\NullSchemaProvider;
use AppDevPanel\Adapter\Laravel\Middleware\DebugCollectors;
use AppDevPanel\Adapter\Laravel\Middleware\DebugMiddleware;
use AppDevPanel\Adapter\Laravel\Proxy\LaravelEventDispatcherProxy;
use AppDevPanel\Adapter\Laravel\Proxy\LaravelTranslatorProxy;
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
use AppDevPanel\Api\Llm\Controller\LlmController;
use AppDevPanel\Api\Llm\FileLlmHistoryStorage;
use AppDevPanel\Api\Llm\FileLlmSettings;
use AppDevPanel\Api\Llm\LlmHistoryStorageInterface;
use AppDevPanel\Api\Llm\LlmSettingsInterface;
use AppDevPanel\Api\Mcp\Controller\McpController;
use AppDevPanel\Api\Mcp\Controller\McpSettingsController;
use AppDevPanel\Api\Mcp\McpSettings;
use AppDevPanel\Api\Middleware\IpFilterMiddleware;
use AppDevPanel\Api\NullPathMapper;
use AppDevPanel\Api\PathMapper;
use AppDevPanel\Api\PathMapperInterface;
use AppDevPanel\Api\PathResolver;
use AppDevPanel\Api\PathResolverInterface;
use AppDevPanel\Cli\Command\DebugQueryCommand;
use AppDevPanel\Cli\Command\DebugResetCommand;
use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CodeCoverageCollector;
use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\DeprecationCollector;
use AppDevPanel\Kernel\Collector\EnvironmentCollector;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\LoggerInterfaceProxy;
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\OpenTelemetryCollector;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\RouterCollector;
use AppDevPanel\Kernel\Collector\SecurityCollector;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector;
use AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use AppDevPanel\Kernel\Collector\ValidatorCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\DebuggerIgnoreConfig;
use AppDevPanel\Kernel\Service\FileServiceRegistry;
use AppDevPanel\Kernel\Service\ServiceRegistryInterface;
use AppDevPanel\Kernel\Storage\FileStorage;
use AppDevPanel\Kernel\Storage\StorageInterface;
use AppDevPanel\McpServer\McpServer;
use AppDevPanel\McpServer\McpToolRegistryFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

final class AppDevPanelServiceProvider extends ServiceProvider
{
    /**
     * @var list<class-string>
     */
    private array $collectorClasses = [];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/app-dev-panel.php', 'app-dev-panel');

        if (!$this->isEnabled()) {
            return;
        }

        $this->registerCoreServices();
        $this->registerCollectors();
        $this->registerDebugger();
        $this->registerDebugCollectors();
        $this->registerApiServices();
        $this->registerCliCommands();
    }

    public function boot(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/app-dev-panel.php' => $this->app->configPath('app-dev-panel.php'),
        ], 'app-dev-panel-config');

        $this->loadRoutesFrom(__DIR__ . '/../routes/adp.php');

        $this->registerMiddleware();
        $this->registerEventListeners();
        $this->decoratePsrServices();
    }

    private function isEnabled(): bool
    {
        return (bool) $this->app->make('config')->get('app-dev-panel.enabled', false);
    }

    private function registerCoreServices(): void
    {
        $config = $this->app->make('config');

        $this->app->singleton(DebuggerIdGenerator::class);

        $this->app->singleton(StorageInterface::class, function () use ($config): FileStorage {
            return new FileStorage(
                $config->get('app-dev-panel.storage.path'),
                $this->app->make(DebuggerIdGenerator::class),
                $config->get('app-dev-panel.dumper.excluded_classes', []),
            );
        });

        $this->app->singleton(TimelineCollector::class);
        $this->collectorClasses[] = TimelineCollector::class;
    }

    private function registerCollectors(): void
    {
        $collectors = $this->app->make('config')->get('app-dev-panel.collectors', []);

        $this->registerSimpleCollectors($collectors);
        $this->registerTimelineCollectors($collectors);
        $this->registerRequestCollectors($collectors);
        $this->registerCommandCollectors($collectors);
        $this->registerRouterCollector($collectors);
    }

    /**
     * Register collectors that need no constructor arguments.
     *
     * @param array<string, bool> $collectors
     */
    private function registerSimpleCollectors(array $collectors): void
    {
        $simpleCollectors = [
            'environment' => EnvironmentCollector::class,
            'filesystem_stream' => FilesystemStreamCollector::class,
            'http_stream' => HttpStreamCollector::class,
            'validator' => ValidatorCollector::class,
            'translator' => TranslatorCollector::class,
            'security' => SecurityCollector::class,
        ];

        foreach ($simpleCollectors as $key => $class) {
            if (!($collectors[$key] ?? true)) {
                continue;
            }
            $this->app->singleton($class);
            $this->collectorClasses[] = $class;
        }
    }

    /**
     * Register collectors that require only TimelineCollector.
     *
     * @param array<string, bool> $collectors
     */
    private function registerTimelineCollectors(array $collectors): void
    {
        $timelineCollectors = [
            'exception' => ExceptionCollector::class,
            'log' => LogCollector::class,
            'event' => EventCollector::class,
            'service' => ServiceCollector::class,
            'http_client' => HttpClientCollector::class,
            'var_dumper' => VarDumperCollector::class,
            'deprecation' => DeprecationCollector::class,
            'database' => DatabaseCollector::class,
            'cache' => CacheCollector::class,
            'mailer' => MailerCollector::class,
            'queue' => QueueCollector::class,
            'opentelemetry' => OpenTelemetryCollector::class,
        ];

        foreach ($timelineCollectors as $key => $class) {
            if (!($collectors[$key] ?? true)) {
                continue;
            }
            $this->app->singleton($class, fn() => new $class($this->app->make(TimelineCollector::class)));
            $this->collectorClasses[] = $class;
        }

        if ($collectors['code_coverage'] ?? false) {
            $this->app->singleton(
                CodeCoverageCollector::class,
                fn() => new CodeCoverageCollector($this->app->make(TimelineCollector::class), [], ['vendor']),
            );
            $this->collectorClasses[] = CodeCoverageCollector::class;
        }
    }

    /**
     * Register request-related collectors (RequestCollector + WebAppInfoCollector).
     *
     * @param array<string, bool> $collectors
     */
    private function registerRequestCollectors(array $collectors): void
    {
        if (!($collectors['request'] ?? true)) {
            return;
        }

        $this->app->singleton(
            RequestCollector::class,
            fn() => new RequestCollector($this->app->make(TimelineCollector::class)),
        );
        $this->collectorClasses[] = RequestCollector::class;

        $this->app->singleton(
            WebAppInfoCollector::class,
            fn() => new WebAppInfoCollector($this->app->make(TimelineCollector::class), 'Laravel'),
        );
        $this->collectorClasses[] = WebAppInfoCollector::class;
    }

    /**
     * Register command-related collectors (CommandCollector + ConsoleAppInfoCollector).
     *
     * @param array<string, bool> $collectors
     */
    private function registerCommandCollectors(array $collectors): void
    {
        if (!($collectors['command'] ?? true)) {
            return;
        }

        $this->app->singleton(
            CommandCollector::class,
            fn() => new CommandCollector($this->app->make(TimelineCollector::class)),
        );
        $this->collectorClasses[] = CommandCollector::class;

        $this->app->singleton(
            ConsoleAppInfoCollector::class,
            fn() => new ConsoleAppInfoCollector($this->app->make(TimelineCollector::class), 'Laravel'),
        );
        $this->collectorClasses[] = ConsoleAppInfoCollector::class;
    }

    /**
     * Register RouterCollector and RouterDataExtractor.
     *
     * @param array<string, bool> $collectors
     */
    private function registerRouterCollector(array $collectors): void
    {
        if (!($collectors['router'] ?? true)) {
            return;
        }

        $this->app->singleton(RouterCollector::class);
        $this->collectorClasses[] = RouterCollector::class;

        $this->app->singleton(
            RouterDataExtractor::class,
            fn() => new RouterDataExtractor($this->app->make(RouterCollector::class), $this->app->make('router')),
        );
    }

    private function registerDebugger(): void
    {
        $this->app->singleton(Debugger::class, function (): Debugger {
            $config = $this->app->make('config');
            $collectors = [];

            foreach ($this->collectorClasses as $class) {
                $collectors[] = $this->app->make($class);
            }

            return new Debugger(
                $this->app->make(DebuggerIdGenerator::class),
                $this->app->make(StorageInterface::class),
                $collectors,
                new DebuggerIgnoreConfig(
                    $config->get('app-dev-panel.ignored_requests', []),
                    $config->get('app-dev-panel.ignored_commands', []),
                ),
            );
        });
    }

    private function registerDebugCollectors(): void
    {
        $this->app->singleton(
            DebugCollectors::class,
            fn() => new DebugCollectors(
                request: $this->app->bound(RequestCollector::class) ? $this->app->make(RequestCollector::class) : null,
                webAppInfo: $this->app->bound(WebAppInfoCollector::class)
                    ? $this->app->make(WebAppInfoCollector::class)
                    : null,
                exception: $this->app->bound(ExceptionCollector::class)
                    ? $this->app->make(ExceptionCollector::class)
                    : null,
                varDumper: $this->app->bound(VarDumperCollector::class)
                    ? $this->app->make(VarDumperCollector::class)
                    : null,
                environment: $this->app->bound(EnvironmentCollector::class)
                    ? $this->app->make(EnvironmentCollector::class)
                    : null,
                routerDataExtractor: $this->app->bound(RouterDataExtractor::class)
                    ? $this->app->make(RouterDataExtractor::class)
                    : null,
            ),
        );
    }

    private function registerApiServices(): void
    {
        $config = $this->app->make('config');

        if (!$config->get('app-dev-panel.api.enabled', true)) {
            return;
        }

        $this->registerPsrFactories();
        $this->registerApiCoreServices($config);
        $this->registerApiMiddleware($config);
        $this->registerApiControllers();
        $this->registerInspectorServices();
        $this->registerApiApplication();
    }

    private function registerPsrFactories(): void
    {
        $this->app->singletonIf(RequestFactoryInterface::class, HttpFactory::class);
        $this->app->singletonIf(ResponseFactoryInterface::class, HttpFactory::class);
        $this->app->singletonIf(StreamFactoryInterface::class, HttpFactory::class);
        $this->app->singletonIf(UriFactoryInterface::class, HttpFactory::class);
        $this->app->singletonIf(ClientInterface::class, static fn() => new Client(['timeout' => 10]));
    }

    /**
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    private function registerApiCoreServices(mixed $config): void
    {
        $this->app->singleton(
            PathResolverInterface::class,
            static fn() => new PathResolver(base_path(), storage_path()),
        );

        $this->app->singleton(PathMapperInterface::class, function (): PathMapperInterface {
            $rules = $this->app->make('config')->get('app-dev-panel.path_mapping', []);
            return $rules !== [] ? new PathMapper($rules) : new NullPathMapper();
        });

        $this->app->singleton(
            JsonResponseFactoryInterface::class,
            fn() => new JsonResponseFactory(
                $this->app->make(ResponseFactoryInterface::class),
                $this->app->make(StreamFactoryInterface::class),
            ),
        );

        $this->app->singleton(
            ServiceRegistryInterface::class,
            static fn() => new FileServiceRegistry($config->get('app-dev-panel.storage.path') . '/services'),
        );

        $this->app->singleton(
            CollectorRepositoryInterface::class,
            fn() => new CollectorRepository($this->app->make(StorageInterface::class)),
        );
    }

    /**
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    private function registerApiMiddleware(mixed $config): void
    {
        $this->app->singleton(
            IpFilterMiddleware::class,
            fn() => new IpFilterMiddleware(
                $this->app->make(ResponseFactoryInterface::class),
                $this->app->make(StreamFactoryInterface::class),
                $config->get('app-dev-panel.api.allowed_ips', ['127.0.0.1', '::1']),
            ),
        );

        $this->app->singleton(
            TokenAuthMiddleware::class,
            fn() => new TokenAuthMiddleware(
                $this->app->make(ResponseFactoryInterface::class),
                $this->app->make(StreamFactoryInterface::class),
                $config->get('app-dev-panel.api.auth_token', ''),
            ),
        );

        $this->app->singleton(
            ResponseDataWrapper::class,
            fn() => new ResponseDataWrapper($this->app->make(JsonResponseFactoryInterface::class)),
        );

        $this->app->singleton(
            InspectorProxyMiddleware::class,
            fn() => new InspectorProxyMiddleware(
                $this->app->make(ServiceRegistryInterface::class),
                $this->app->make(ClientInterface::class),
                $this->app->make(ResponseFactoryInterface::class),
                $this->app->make(StreamFactoryInterface::class),
                $this->app->make(UriFactoryInterface::class),
            ),
        );
    }

    private function registerApiControllers(): void
    {
        $this->app->singleton(
            DebugController::class,
            fn() => new DebugController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->make(CollectorRepositoryInterface::class),
                $this->app->make(StorageInterface::class),
                $this->app->make(ResponseFactoryInterface::class),
            ),
        );

        $this->app->singleton(
            IngestionController::class,
            fn() => new IngestionController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->make(StorageInterface::class),
            ),
        );

        $this->app->singleton(
            ServiceController::class,
            fn() => new ServiceController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->make(ServiceRegistryInterface::class),
            ),
        );

        $this->app->singleton(
            FileController::class,
            fn() => new FileController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->make(PathResolverInterface::class),
                $this->app->make(PathMapperInterface::class),
            ),
        );

        $this->app->singleton(
            SettingsController::class,
            fn() => new SettingsController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->make(PathMapperInterface::class),
            ),
        );

        $this->app->singleton(
            GitRepositoryProvider::class,
            fn() => new GitRepositoryProvider($this->app->make(PathResolverInterface::class)),
        );

        $this->app->singleton(
            GitController::class,
            fn() => new GitController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->make(GitRepositoryProvider::class),
            ),
        );

        $this->app->singleton(
            ComposerController::class,
            fn() => new ComposerController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->make(PathResolverInterface::class),
            ),
        );

        $this->app->singleton(
            OpcacheController::class,
            fn() => new OpcacheController($this->app->make(JsonResponseFactoryInterface::class)),
        );

        $this->app->singleton(
            CodeCoverageController::class,
            fn() => new CodeCoverageController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->make(PathResolverInterface::class),
            ),
        );

        $this->app->singleton(
            RequestController::class,
            fn() => new RequestController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->make(CollectorRepositoryInterface::class),
            ),
        );

        $this->app->singleton(McpSettings::class, fn() => new McpSettings($config->get('app-dev-panel.storage.path')));

        $this->app->singleton(
            McpServer::class,
            fn() => new McpServer(McpToolRegistryFactory::create($this->app->make(StorageInterface::class))),
        );

        $this->app->singleton(
            McpController::class,
            fn() => new McpController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->make(McpServer::class),
                $this->app->make(McpSettings::class),
            ),
        );

        $this->app->singleton(
            McpSettingsController::class,
            fn() => new McpSettingsController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->make(McpSettings::class),
            ),
        );

        $this->app->singleton(
            LlmSettingsInterface::class,
            fn() => new FileLlmSettings($this->app->make('config')->get('app-dev-panel.storage.path')),
        );

        $this->app->singleton(
            LlmHistoryStorageInterface::class,
            fn() => new FileLlmHistoryStorage($this->app->make('config')->get('app-dev-panel.storage.path')),
        );

        $this->app->singleton(
            LlmController::class,
            fn() => new LlmController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->make(LlmSettingsInterface::class),
                $this->app->make(ClientInterface::class),
                $this->app->make(RequestFactoryInterface::class),
                $this->app->make(StreamFactoryInterface::class),
                $this->app->make(LlmHistoryStorageInterface::class),
            ),
        );
    }

    private function registerInspectorServices(): void
    {
        $this->app->singleton(SchemaProviderInterface::class, function () {
            if ($this->app->bound('db')) {
                try {
                    $connection = $this->app->make('db')->connection();
                    return new LaravelSchemaProvider($connection);
                } catch (\Throwable) {
                    // Silently ignore: database connection may not be available (e.g., misconfigured or unavailable).
                    // Fall through to NullSchemaProvider below.
                    unset($connection);
                }
            }
            return new NullSchemaProvider();
        });

        $this->app->singleton(
            DatabaseController::class,
            fn() => new DatabaseController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->make(SchemaProviderInterface::class),
            ),
        );

        $this->app->singleton(
            AuthorizationConfigProviderInterface::class,
            fn() => new NullAuthorizationConfigProvider(),
        );
        $this->app->singleton(
            AuthorizationController::class,
            fn() => new AuthorizationController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->make(AuthorizationConfigProviderInterface::class),
            ),
        );

        $this->app->singleton(LaravelConfigProvider::class, fn() => new LaravelConfigProvider($this->app));
        $this->app->alias(LaravelConfigProvider::class, 'config.adp');

        $this->app->singleton(
            InspectController::class,
            fn() => new InspectController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app,
                $this->app->make('config')->all(),
            ),
        );

        $this->app->singleton(
            InspectorCacheController::class,
            fn() => new InspectorCacheController($this->app->make(JsonResponseFactoryInterface::class), $this->app),
        );

        $this->app->singleton(
            TranslationController::class,
            fn() => new TranslationController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->bound('log') ? $this->app->make('log') : null,
                $this->app,
            ),
        );

        $this->app->singleton(
            CommandController::class,
            fn() => new CommandController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->make(PathResolverInterface::class),
                $this->app,
            ),
        );

        $this->app->singleton(
            LaravelRouteCollectionAdapter::class,
            fn() => new LaravelRouteCollectionAdapter($this->app->make('router')),
        );

        $this->app->singleton(
            LaravelUrlMatcherAdapter::class,
            fn() => new LaravelUrlMatcherAdapter($this->app->make('router')),
        );

        $this->app->singleton(
            RoutingController::class,
            fn() => new RoutingController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->make(LaravelRouteCollectionAdapter::class),
                $this->app->make(LaravelUrlMatcherAdapter::class),
            ),
        );
    }

    private function registerApiApplication(): void
    {
        $this->app->singleton(
            ApiApplication::class,
            fn() => new ApiApplication(
                $this->app,
                $this->app->make(ResponseFactoryInterface::class),
                $this->app->make(StreamFactoryInterface::class),
            ),
        );

        $this->app->singleton(
            AdpApiController::class,
            fn() => new AdpApiController($this->app->make(ApiApplication::class)),
        );
    }

    private function registerCliCommands(): void
    {
        $this->commands([
            DebugResetCommand::class,
            DebugQueryCommand::class,
        ]);

        $this->app
            ->when(DebugResetCommand::class)
            ->needs(Debugger::class)
            ->give(fn() => $this->app->make(Debugger::class));
    }

    private function registerMiddleware(): void
    {
        if ($this->app->bound(HttpKernelContract::class)) {
            $kernel = $this->app->make(HttpKernelContract::class);
            if (method_exists($kernel, 'pushMiddleware')) {
                $kernel->pushMiddleware(DebugMiddleware::class);
            }
        }
    }

    private function registerEventListeners(): void
    {
        $events = $this->app->make('events');
        $collectors = $this->app->make('config')->get('app-dev-panel.collectors', []);

        $simpleListeners = [
            'database' => [DatabaseListener::class, DatabaseCollector::class],
            'cache' => [CacheListener::class, CacheCollector::class],
            'mailer' => [MailListener::class, MailerCollector::class],
            'queue' => [QueueListener::class, QueueCollector::class],
            'http_client' => [HttpClientListener::class, HttpClientCollector::class],
        ];

        foreach ($simpleListeners as $key => [$listenerClass, $collectorClass]) {
            if (!($collectors[$key] ?? true)) {
                continue;
            }
            $listener = new $listenerClass(fn() => $this->app->make($collectorClass));
            $listener->register($events);
        }

        if ($collectors['security'] ?? true) {
            $listener = new SecurityListener(fn() => $this->app->make(SecurityCollector::class));
            $listener->register($events);
        }

        if ($collectors['command'] ?? true) {
            $listener = new ConsoleListener(
                fn() => $this->app->make(Debugger::class),
                fn() => $this->app->make(CommandCollector::class),
                fn() => $this->app->make(ConsoleAppInfoCollector::class),
                fn() => $this->app->make(ExceptionCollector::class),
                fn() => $this->app->make(EnvironmentCollector::class),
            );
            $listener->register($events);
        }
    }

    private function decoratePsrServices(): void
    {
        $collectors = $this->app->make('config')->get('app-dev-panel.collectors', []);

        $this->decorateLoggerProxy($collectors);
        $this->decorateHttpClientProxy($collectors);
        $this->decorateEventDispatcherProxy($collectors);
        $this->decorateTranslatorProxy($collectors);
    }

    /**
     * @param array<string, bool> $collectors
     */
    private function decorateLoggerProxy(array $collectors): void
    {
        if (!(($collectors['log'] ?? true) && $this->app->bound(LogCollector::class))) {
            return;
        }

        $this->app->extend(\Psr\Log\LoggerInterface::class, function ($logger) {
            if ($logger instanceof LoggerInterfaceProxy) {
                return $logger;
            }
            return new LoggerInterfaceProxy($logger, $this->app->make(LogCollector::class));
        });
    }

    /**
     * @param array<string, bool> $collectors
     */
    private function decorateHttpClientProxy(array $collectors): void
    {
        if (!(($collectors['http_client'] ?? true) && $this->app->bound(HttpClientCollector::class))) {
            return;
        }
        if (!$this->app->bound(ClientInterface::class)) {
            return;
        }

        $this->app->extend(ClientInterface::class, function ($client) {
            if ($client instanceof HttpClientInterfaceProxy) {
                return $client;
            }
            return new HttpClientInterfaceProxy($client, $this->app->make(HttpClientCollector::class));
        });
    }

    /**
     * @param array<string, bool> $collectors
     */
    private function decorateEventDispatcherProxy(array $collectors): void
    {
        if (!(($collectors['event'] ?? true) && $this->app->bound(EventCollector::class))) {
            return;
        }

        $this->app->extend('events', function ($dispatcher) {
            if ($dispatcher instanceof LaravelEventDispatcherProxy) {
                return $dispatcher;
            }
            return new LaravelEventDispatcherProxy($dispatcher, $this->app->make(EventCollector::class));
        });
    }

    /**
     * @param array<string, bool> $collectors
     */
    private function decorateTranslatorProxy(array $collectors): void
    {
        if (!(($collectors['translator'] ?? true) && $this->app->bound(TranslatorCollector::class))) {
            return;
        }
        if (!$this->app->bound('translator')) {
            return;
        }

        $this->app->extend('translator', function ($translator) {
            if ($translator instanceof LaravelTranslatorProxy) {
                return $translator;
            }
            return new LaravelTranslatorProxy($translator, $this->app->make(TranslatorCollector::class));
        });
    }
}
