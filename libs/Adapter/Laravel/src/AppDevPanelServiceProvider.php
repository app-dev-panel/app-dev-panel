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
use AppDevPanel\Adapter\Laravel\Inspector\LaravelConfigProvider;
use AppDevPanel\Adapter\Laravel\Inspector\LaravelRouteCollectionAdapter;
use AppDevPanel\Adapter\Laravel\Inspector\LaravelSchemaProvider;
use AppDevPanel\Adapter\Laravel\Inspector\LaravelUrlMatcherAdapter;
use AppDevPanel\Adapter\Laravel\Inspector\NullSchemaProvider;
use AppDevPanel\Adapter\Laravel\Middleware\DebugMiddleware;
use AppDevPanel\Adapter\Laravel\Proxy\LaravelEventDispatcherProxy;
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
use AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\LoggerInterfaceProxy;
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\RouterCollector;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector;
use AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector;
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
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Client\ClientInterface;
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

        if ($collectors['environment'] ?? true) {
            $this->app->singleton(EnvironmentCollector::class);
            $this->collectorClasses[] = EnvironmentCollector::class;
        }

        if ($collectors['request'] ?? true) {
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

        if ($collectors['exception'] ?? true) {
            $this->app->singleton(
                ExceptionCollector::class,
                fn() => new ExceptionCollector($this->app->make(TimelineCollector::class)),
            );
            $this->collectorClasses[] = ExceptionCollector::class;
        }

        if ($collectors['log'] ?? true) {
            $this->app->singleton(
                LogCollector::class,
                fn() => new LogCollector($this->app->make(TimelineCollector::class)),
            );
            $this->collectorClasses[] = LogCollector::class;
        }

        if ($collectors['event'] ?? true) {
            $this->app->singleton(
                EventCollector::class,
                fn() => new EventCollector($this->app->make(TimelineCollector::class)),
            );
            $this->collectorClasses[] = EventCollector::class;
        }

        if ($collectors['service'] ?? true) {
            $this->app->singleton(
                ServiceCollector::class,
                fn() => new ServiceCollector($this->app->make(TimelineCollector::class)),
            );
            $this->collectorClasses[] = ServiceCollector::class;
        }

        if ($collectors['http_client'] ?? true) {
            $this->app->singleton(
                HttpClientCollector::class,
                fn() => new HttpClientCollector($this->app->make(TimelineCollector::class)),
            );
            $this->collectorClasses[] = HttpClientCollector::class;
        }

        if ($collectors['var_dumper'] ?? true) {
            $this->app->singleton(
                VarDumperCollector::class,
                fn() => new VarDumperCollector($this->app->make(TimelineCollector::class)),
            );
            $this->collectorClasses[] = VarDumperCollector::class;
        }

        if ($collectors['filesystem_stream'] ?? true) {
            $this->app->singleton(FilesystemStreamCollector::class);
            $this->collectorClasses[] = FilesystemStreamCollector::class;
        }

        if ($collectors['http_stream'] ?? true) {
            $this->app->singleton(HttpStreamCollector::class);
            $this->collectorClasses[] = HttpStreamCollector::class;
        }

        if ($collectors['command'] ?? true) {
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

        if ($collectors['database'] ?? true) {
            $this->app->singleton(
                DatabaseCollector::class,
                fn() => new DatabaseCollector($this->app->make(TimelineCollector::class)),
            );
            $this->collectorClasses[] = DatabaseCollector::class;
        }

        if ($collectors['cache'] ?? true) {
            $this->app->singleton(
                CacheCollector::class,
                fn() => new CacheCollector($this->app->make(TimelineCollector::class)),
            );
            $this->collectorClasses[] = CacheCollector::class;
        }

        if ($collectors['mailer'] ?? true) {
            $this->app->singleton(
                MailerCollector::class,
                fn() => new MailerCollector($this->app->make(TimelineCollector::class)),
            );
            $this->collectorClasses[] = MailerCollector::class;
        }

        if ($collectors['queue'] ?? true) {
            $this->app->singleton(
                QueueCollector::class,
                fn() => new QueueCollector($this->app->make(TimelineCollector::class)),
            );
            $this->collectorClasses[] = QueueCollector::class;
        }

        if ($collectors['validator'] ?? true) {
            $this->app->singleton(ValidatorCollector::class);
            $this->collectorClasses[] = ValidatorCollector::class;
        }

        if ($collectors['router'] ?? true) {
            $this->app->singleton(RouterCollector::class);
            $this->collectorClasses[] = RouterCollector::class;

            $this->app->singleton(
                RouterDataExtractor::class,
                fn() => new RouterDataExtractor($this->app->make(RouterCollector::class), $this->app->make('router')),
            );
        }
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
                $config->get('app-dev-panel.ignored_requests', []),
                $config->get('app-dev-panel.ignored_commands', []),
            );
        });
    }

    private function registerApiServices(): void
    {
        $config = $this->app->make('config');

        if (!$config->get('app-dev-panel.api.enabled', true)) {
            return;
        }

        // PSR-17 factories
        $this->app->singletonIf(ResponseFactoryInterface::class, HttpFactory::class);
        $this->app->singletonIf(StreamFactoryInterface::class, HttpFactory::class);
        $this->app->singletonIf(UriFactoryInterface::class, HttpFactory::class);
        $this->app->singletonIf(ClientInterface::class, static fn() => new Client(['timeout' => 10]));

        // Path resolver
        $this->app->singleton(
            PathResolverInterface::class,
            static fn() => new PathResolver(base_path(), storage_path()),
        );

        // JSON response factory
        $this->app->singleton(
            JsonResponseFactoryInterface::class,
            fn() => new JsonResponseFactory(
                $this->app->make(ResponseFactoryInterface::class),
                $this->app->make(StreamFactoryInterface::class),
            ),
        );

        // Service registry
        $this->app->singleton(
            ServiceRegistryInterface::class,
            static fn() => new FileServiceRegistry($config->get('app-dev-panel.storage.path') . '/services'),
        );

        // Collector repository
        $this->app->singleton(
            CollectorRepositoryInterface::class,
            fn() => new CollectorRepository($this->app->make(StorageInterface::class)),
        );

        // Middleware
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

        // Controllers
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

        // Database inspector
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

        // Laravel config provider for inspector
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

        // Laravel route inspection adapters
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

        $this->app->singleton(
            RequestController::class,
            fn() => new RequestController(
                $this->app->make(JsonResponseFactoryInterface::class),
                $this->app->make(CollectorRepositoryInterface::class),
            ),
        );

        // ApiApplication
        $this->app->singleton(
            ApiApplication::class,
            fn() => new ApiApplication(
                $this->app,
                $this->app->make(ResponseFactoryInterface::class),
                $this->app->make(StreamFactoryInterface::class),
            ),
        );

        // Bridge controller
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

        if ($collectors['database'] ?? true) {
            $listener = new DatabaseListener(fn() => $this->app->make(DatabaseCollector::class));
            $listener->register($events);
        }

        if ($collectors['cache'] ?? true) {
            $listener = new CacheListener(fn() => $this->app->make(CacheCollector::class));
            $listener->register($events);
        }

        if ($collectors['mailer'] ?? true) {
            $listener = new MailListener(fn() => $this->app->make(MailerCollector::class));
            $listener->register($events);
        }

        if ($collectors['queue'] ?? true) {
            $listener = new QueueListener(fn() => $this->app->make(QueueCollector::class));
            $listener->register($events);
        }

        if ($collectors['http_client'] ?? true) {
            $listener = new HttpClientListener(fn() => $this->app->make(HttpClientCollector::class));
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

        if (($collectors['log'] ?? true) && $this->app->bound(LogCollector::class)) {
            $this->app->extend(\Psr\Log\LoggerInterface::class, function ($logger) {
                if ($logger instanceof LoggerInterfaceProxy) {
                    return $logger;
                }
                return new LoggerInterfaceProxy($logger, $this->app->make(LogCollector::class));
            });
        }

        if (($collectors['http_client'] ?? true) && $this->app->bound(HttpClientCollector::class)) {
            if ($this->app->bound(ClientInterface::class)) {
                $this->app->extend(ClientInterface::class, function ($client) {
                    if ($client instanceof HttpClientInterfaceProxy) {
                        return $client;
                    }
                    return new HttpClientInterfaceProxy($client, $this->app->make(HttpClientCollector::class));
                });
            }
        }

        if (($collectors['event'] ?? true) && $this->app->bound(EventCollector::class)) {
            $this->app->extend('events', function ($dispatcher) {
                if ($dispatcher instanceof LaravelEventDispatcherProxy) {
                    return $dispatcher;
                }
                return new LaravelEventDispatcherProxy($dispatcher, $this->app->make(EventCollector::class));
            });
        }
    }
}
