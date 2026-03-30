<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2;

use AppDevPanel\Adapter\Yii2\Collector\DbProfilingTarget;
use AppDevPanel\Adapter\Yii2\Collector\DebugLogTarget;
use AppDevPanel\Adapter\Yii2\Controller\DebugQueryController;
use AppDevPanel\Adapter\Yii2\Controller\DebugResetController;
use AppDevPanel\Adapter\Yii2\EventListener\ConsoleListener;
use AppDevPanel\Adapter\Yii2\EventListener\WebListener;
use AppDevPanel\Adapter\Yii2\Inspector\NullSchemaProvider;
use AppDevPanel\Adapter\Yii2\Inspector\Yii2ConfigProvider;
use AppDevPanel\Adapter\Yii2\Inspector\Yii2DbSchemaProvider;
use AppDevPanel\Adapter\Yii2\Inspector\Yii2RouteCollection;
use AppDevPanel\Adapter\Yii2\Proxy\I18NProxy;
use AppDevPanel\Adapter\Yii2\Proxy\RouterMatchRecorder;
use AppDevPanel\Adapter\Yii2\Proxy\UrlRuleProxy;
use AppDevPanel\Api\ApiApplication;
use AppDevPanel\Api\Debug\Controller\SettingsController;
use AppDevPanel\Api\Debug\Middleware\ResponseDataWrapper;
use AppDevPanel\Api\Debug\Repository\CollectorRepository;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Http\JsonResponseFactory;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Inspector\Authorization\AuthorizationConfigProviderInterface;
use AppDevPanel\Api\Inspector\Authorization\NullAuthorizationConfigProvider;
use AppDevPanel\Api\Inspector\Controller\AuthorizationController;
use AppDevPanel\Api\Inspector\Controller\CacheController;
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
use AppDevPanel\Api\Panel\PanelConfig;
use AppDevPanel\Api\Panel\PanelController;
use AppDevPanel\Api\PathMapper;
use AppDevPanel\Api\PathMapperInterface;
use AppDevPanel\Api\PathResolver;
use AppDevPanel\Api\PathResolverInterface;
use AppDevPanel\Kernel\Collector\AssetBundleCollector;
use AppDevPanel\Kernel\Collector\AuthorizationCollector;
use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
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
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\OpenTelemetryCollector;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\RouterCollector;
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
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\console\Application as ConsoleApplication;
use yii\web\Application as WebApplication;

/**
 * ADP debug module for Yii 2.
 *
 * Registers collectors, proxies, event listeners, and API routes.
 * Equivalent to Symfony's AppDevPanelBundle + AppDevPanelExtension.
 */
class Module extends \yii\base\Module implements BootstrapInterface
{
    /**
     * @var bool Master switch to enable/disable the debug panel.
     */
    public bool $enabled = true;

    /**
     * @var string Directory for debug data storage.
     */
    public string $storagePath = '@runtime/debug';

    /**
     * @var int Maximum number of debug entries to keep.
     */
    public int $historySize = FileStorage::DEFAULT_HISTORY_SIZE;

    /**
     * @var array<string, bool> Collector toggle map.
     */
    public array $collectors = [
        'request' => true,
        'exception' => true,
        'log' => true,
        'event' => true,
        'service' => true,
        'http_client' => true,
        'timeline' => true,
        'var_dumper' => true,
        'deprecation' => true,
        'filesystem_stream' => true,
        'http_stream' => true,
        'command' => true,
        'db' => true,
        'mailer' => true,
        'assets' => true,
        'environment' => true,
        'cache' => true,
        'router' => true,
        'queue' => true,
        'validator' => true,
        'translator' => true,
    ];

    /**
     * @var string[] URL patterns to ignore (wildcard).
     */
    public array $ignoredRequests = ['/debug/api/**', '/inspect/api/**'];

    /**
     * @var string[] Command name patterns to ignore (wildcard).
     */
    public array $ignoredCommands = ['help', 'list', 'cache/*', 'asset/*'];

    /**
     * @var string[] Classes to exclude from object dumps.
     */
    public array $excludedClasses = [];

    /**
     * @var string[] IP addresses allowed to access the API.
     */
    public array $allowedIps = ['127.0.0.1', '::1'];

    /**
     * @var string Authentication token for API access (empty = no auth).
     */
    public string $authToken = '';

    /**
     * @var array<string, string> Remote-to-local path mapping for Docker/Vagrant.
     *                            Keys are remote (container) prefixes, values are local (host) prefixes.
     *                            Example: ['/app' => '/home/user/project']
     */
    public array $pathMapping = [];

    public $controllerNamespace = 'AppDevPanel\\Adapter\\Yii2\\Controller';

    private ?Debugger $debugger = null;
    private ?TimelineCollector $timelineCollector = null;
    private ?RouterMatchRecorder $matchRecorder = null;

    /** @var CollectorInterface[] */
    private array $collectorInstances = [];

    public function init(): void
    {
        parent::init();

        // Register namespace alias so Yii 2 can resolve the controller path.
        // Without this, getControllerPath() converts controllerNamespace to
        // @AppDevPanel/Adapter/Yii2/Controller which doesn't exist as an alias.
        \Yii::setAlias('@AppDevPanel/Adapter/Yii2', __DIR__);
    }

    public function bootstrap($app): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->registerServices($app);
        $this->registerCollectors();
        $this->buildDebugger();
        $this->registerRoutes($app);
        $this->wrapUrlRules($app);
        $this->registerEventListeners($app);
        $this->registerConsoleCommands($app);
    }

    public function getDebugger(): Debugger
    {
        if ($this->debugger === null) {
            throw new \RuntimeException('Debugger is not initialized. Ensure the module is bootstrapped.');
        }
        return $this->debugger;
    }

    public function getTimelineCollector(): TimelineCollector
    {
        if ($this->timelineCollector === null) {
            $this->timelineCollector = new TimelineCollector();
        }
        return $this->timelineCollector;
    }

    /**
     * @return CollectorInterface[]
     */
    public function getCollectorInstances(): array
    {
        return $this->collectorInstances;
    }

    public function getCollector(string $class): ?CollectorInterface
    {
        foreach ($this->collectorInstances as $collector) {
            if ($collector instanceof $class) {
                return $collector;
            }
        }
        return null;
    }

    private function registerServices(?Application $app = null): void
    {
        $storagePath = \Yii::getAlias($this->storagePath);

        $this->registerCoreServices($storagePath);
        $this->registerMiddleware($storagePath);
        $containerBridge = $this->registerContainerBridge();
        $this->registerApiApplication($containerBridge);
        $this->registerInspectorControllers($app, $containerBridge);
    }

    private function registerCoreServices(string $storagePath): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new FileStorage($storagePath, $idGenerator, $this->excludedClasses);

        $httpFactory = new HttpFactory();

        \Yii::$container->setSingleton(DebuggerIdGenerator::class, $idGenerator);
        \Yii::$container->setSingleton(StorageInterface::class, $storage);
        \Yii::$container->setSingleton(RequestFactoryInterface::class, $httpFactory);
        \Yii::$container->setSingleton(ResponseFactoryInterface::class, $httpFactory);
        \Yii::$container->setSingleton(StreamFactoryInterface::class, $httpFactory);
        \Yii::$container->setSingleton(UriFactoryInterface::class, $httpFactory);

        if (!\Yii::$container->has(ClientInterface::class)) {
            \Yii::$container->setSingleton(ClientInterface::class, static fn() => new Client(['timeout' => 10]));
        }

        $basePath = \Yii::getAlias('@app');
        $runtimePath = \Yii::getAlias('@runtime');

        \Yii::$container->setSingleton(
            PathResolverInterface::class,
            static fn() => new PathResolver($basePath, $runtimePath),
        );

        $pathMapping = $this->pathMapping;
        \Yii::$container->setSingleton(PathMapperInterface::class, static fn() => $pathMapping !== []
            ? new PathMapper($pathMapping)
            : new NullPathMapper());
        \Yii::$container->setSingleton(
            JsonResponseFactoryInterface::class,
            static fn() => new JsonResponseFactory(
                \Yii::$container->get(ResponseFactoryInterface::class),
                \Yii::$container->get(StreamFactoryInterface::class),
            ),
        );
        \Yii::$container->setSingleton(
            ServiceRegistryInterface::class,
            static fn() => new FileServiceRegistry($storagePath . '/services'),
        );
        \Yii::$container->setSingleton(
            CollectorRepositoryInterface::class,
            static fn() => new CollectorRepository(\Yii::$container->get(StorageInterface::class)),
        );

        // Schema provider — use has() instead of isset() to avoid Yii2 magic property exceptions
        if (\Yii::$app !== null && \Yii::$app->has('db')) {
            \Yii::$container->setSingleton(
                SchemaProviderInterface::class,
                static fn() => new Yii2DbSchemaProvider(\Yii::$app->db),
            );
        } else {
            \Yii::$container->setSingleton(SchemaProviderInterface::class, NullSchemaProvider::class);
        }

        // Authorization provider
        \Yii::$container->setSingleton(
            AuthorizationConfigProviderInterface::class,
            NullAuthorizationConfigProvider::class,
        );
        \Yii::$container->setSingleton(
            AuthorizationController::class,
            static fn() => new AuthorizationController(
                \Yii::$container->get(JsonResponseFactoryInterface::class),
                \Yii::$container->get(AuthorizationConfigProviderInterface::class),
            ),
        );

        // Config provider
        \Yii::$container->setSingleton(Yii2ConfigProvider::class, static fn() => new Yii2ConfigProvider(\Yii::$app));
        \Yii::$container->set('config', Yii2ConfigProvider::class);
    }

    private function registerMiddleware(string $storagePath): void
    {
        // Middleware: ResponseDataWrapper (wraps JSON responses, catches NotFoundException → 404)
        \Yii::$container->setSingleton(
            ResponseDataWrapper::class,
            static fn() => new ResponseDataWrapper(\Yii::$container->get(JsonResponseFactoryInterface::class)),
        );

        // Middleware: IpFilterMiddleware
        $allowedIps = $this->allowedIps;
        \Yii::$container->setSingleton(
            IpFilterMiddleware::class,
            static fn() => new IpFilterMiddleware(
                \Yii::$container->get(ResponseFactoryInterface::class),
                \Yii::$container->get(StreamFactoryInterface::class),
                $allowedIps,
            ),
        );

        // Middleware: InspectorProxyMiddleware (proxies /inspect/api to external services)
        \Yii::$container->setSingleton(
            InspectorProxyMiddleware::class,
            static fn() => new InspectorProxyMiddleware(
                \Yii::$container->get(ServiceRegistryInterface::class),
                \Yii::$container->get(ClientInterface::class),
                \Yii::$container->get(ResponseFactoryInterface::class),
                \Yii::$container->get(StreamFactoryInterface::class),
                \Yii::$container->get(UriFactoryInterface::class),
            ),
        );
    }

    private function registerContainerBridge(): \Psr\Container\ContainerInterface
    {
        // PSR-11 Container bridge — wraps Yii 2 container as PSR ContainerInterface.
        // Must be registered so that inspector controllers (resolved via DI) can receive it.
        $containerBridge = new class implements \Psr\Container\ContainerInterface {
            public function get(string $id): mixed
            {
                return \Yii::$container->get($id);
            }

            public function has(string $id): bool
            {
                return \Yii::$container->has($id);
            }
        };
        \Yii::$container->setSingleton(\Psr\Container\ContainerInterface::class, static fn() => $containerBridge);

        return $containerBridge;
    }

    private function registerApiApplication(\Psr\Container\ContainerInterface $containerBridge): void
    {
        \Yii::$container->setSingleton(PanelConfig::class, static fn() => new PanelConfig());
        \Yii::$container->setSingleton(
            PanelController::class,
            static fn() => new PanelController(
                \Yii::$container->get(ResponseFactoryInterface::class),
                \Yii::$container->get(StreamFactoryInterface::class),
                \Yii::$container->get(PanelConfig::class),
            ),
        );

        \Yii::$container->setSingleton(ApiApplication::class, static function () use ($containerBridge) {
            return new ApiApplication(
                $containerBridge,
                \Yii::$container->get(ResponseFactoryInterface::class),
                \Yii::$container->get(StreamFactoryInterface::class),
            );
        });
    }

    private function registerInspectorControllers(
        ?Application $app,
        \Psr\Container\ContainerInterface $containerBridge,
    ): void {
        // Inspector controllers — explicit registration to avoid auto-wiring issues.
        // Each adapter must register these (same pattern as Yiisoft/Symfony adapters).
        \Yii::$container->setSingleton(
            FileController::class,
            static fn() => new FileController(
                \Yii::$container->get(JsonResponseFactoryInterface::class),
                \Yii::$container->get(PathResolverInterface::class),
                \Yii::$container->get(PathMapperInterface::class),
            ),
        );

        \Yii::$container->setSingleton(
            SettingsController::class,
            static fn() => new SettingsController(
                \Yii::$container->get(JsonResponseFactoryInterface::class),
                \Yii::$container->get(PathMapperInterface::class),
            ),
        );

        $routeCollection = $app instanceof WebApplication ? new Yii2RouteCollection($app->getUrlManager()) : null;
        \Yii::$container->setSingleton(
            RoutingController::class,
            static fn() => new RoutingController(
                \Yii::$container->get(JsonResponseFactoryInterface::class),
                $routeCollection,
            ),
        );

        $appParams = $app->params ?? [];
        \Yii::$container->setSingleton(
            InspectController::class,
            static fn() => new InspectController(
                \Yii::$container->get(JsonResponseFactoryInterface::class),
                $containerBridge,
                $appParams,
            ),
        );

        \Yii::$container->setSingleton(
            DatabaseController::class,
            static fn() => new DatabaseController(
                \Yii::$container->get(JsonResponseFactoryInterface::class),
                \Yii::$container->get(SchemaProviderInterface::class),
            ),
        );

        \Yii::$container->setSingleton(
            GitRepositoryProvider::class,
            static fn() => new GitRepositoryProvider(\Yii::$container->get(PathResolverInterface::class)),
        );
        \Yii::$container->setSingleton(
            GitController::class,
            static fn() => new GitController(
                \Yii::$container->get(JsonResponseFactoryInterface::class),
                \Yii::$container->get(GitRepositoryProvider::class),
            ),
        );

        \Yii::$container->setSingleton(
            ServiceController::class,
            static fn() => new ServiceController(
                \Yii::$container->get(JsonResponseFactoryInterface::class),
                \Yii::$container->get(ServiceRegistryInterface::class),
            ),
        );

        \Yii::$container->setSingleton(
            CacheController::class,
            static fn() => new CacheController(
                \Yii::$container->get(JsonResponseFactoryInterface::class),
                $containerBridge,
            ),
        );

        \Yii::$container->setSingleton(
            CommandController::class,
            static fn() => new CommandController(
                \Yii::$container->get(JsonResponseFactoryInterface::class),
                \Yii::$container->get(PathResolverInterface::class),
                $containerBridge,
            ),
        );

        \Yii::$container->setSingleton(
            ComposerController::class,
            static fn() => new ComposerController(
                \Yii::$container->get(JsonResponseFactoryInterface::class),
                \Yii::$container->get(PathResolverInterface::class),
            ),
        );

        \Yii::$container->setSingleton(
            RequestController::class,
            static fn() => new RequestController(
                \Yii::$container->get(JsonResponseFactoryInterface::class),
                \Yii::$container->get(CollectorRepositoryInterface::class),
            ),
        );

        \Yii::$container->setSingleton(
            TranslationController::class,
            static fn() => new TranslationController(
                \Yii::$container->get(JsonResponseFactoryInterface::class),
                new \Psr\Log\NullLogger(),
                \Yii::$container->get(\Psr\Container\ContainerInterface::class),
            ),
        );

        \Yii::$container->setSingleton(
            OpcacheController::class,
            static fn() => new OpcacheController(\Yii::$container->get(JsonResponseFactoryInterface::class)),
        );

        $storagePath = \Yii::getAlias($this->storagePath);

        \Yii::$container->setSingleton(McpSettings::class, static fn() => new McpSettings($storagePath));

        \Yii::$container->setSingleton(
            McpServer::class,
            static fn() => new McpServer(McpToolRegistryFactory::create(\Yii::$container->get(StorageInterface::class))),
        );

        \Yii::$container->setSingleton(
            McpController::class,
            static fn() => new McpController(
                \Yii::$container->get(JsonResponseFactoryInterface::class),
                \Yii::$container->get(McpServer::class),
                \Yii::$container->get(McpSettings::class),
            ),
        );

        \Yii::$container->setSingleton(
            McpSettingsController::class,
            static fn() => new McpSettingsController(
                \Yii::$container->get(JsonResponseFactoryInterface::class),
                \Yii::$container->get(McpSettings::class),
            ),
        );

        $resolvedStoragePath = (string) \Yii::getAlias($this->storagePath);
        \Yii::$container->setSingleton(
            LlmSettingsInterface::class,
            static fn() => new FileLlmSettings($resolvedStoragePath),
        );

        \Yii::$container->setSingleton(
            LlmHistoryStorageInterface::class,
            static fn() => new FileLlmHistoryStorage($resolvedStoragePath),
        );

        \Yii::$container->setSingleton(
            LlmController::class,
            static fn() => new LlmController(
                \Yii::$container->get(JsonResponseFactoryInterface::class),
                \Yii::$container->get(LlmSettingsInterface::class),
                \Yii::$container->get(ClientInterface::class),
                \Yii::$container->get(RequestFactoryInterface::class),
                \Yii::$container->get(StreamFactoryInterface::class),
                \Yii::$container->get(LlmHistoryStorageInterface::class),
            ),
        );
    }

    private function registerCollectors(): void
    {
        $timeline = $this->getTimelineCollector();
        $this->collectorInstances[] = $timeline;

        $collectorMap = $this->buildCollectorMap($timeline);

        foreach ($collectorMap as $key => $factory) {
            if (!($this->collectors[$key] ?? true)) {
                continue;
            }
            foreach ($factory() as $collector) {
                $this->collectorInstances[] = $collector;
            }
        }
    }

    /**
     * @return array<string, \Closure(): list<CollectorInterface>>
     */
    private function buildCollectorMap(TimelineCollector $timeline): array
    {
        return [
            'request' => static fn(): array => [
                new RequestCollector($timeline),
                new WebAppInfoCollector($timeline, 'Yii2'),
            ],
            'exception' => static fn(): array => [new ExceptionCollector($timeline)],
            'deprecation' => static fn(): array => [new DeprecationCollector($timeline)],
            'log' => static fn(): array => [new LogCollector($timeline)],
            'event' => static fn(): array => [new EventCollector($timeline)],
            'service' => static fn(): array => [new ServiceCollector($timeline)],
            'http_client' => static fn(): array => [new HttpClientCollector($timeline)],
            'var_dumper' => static function () use ($timeline): array {
                $varDumperCollector = new VarDumperCollector($timeline);
                \Yii::$container->setSingleton(VarDumperCollector::class, $varDumperCollector);
                return [$varDumperCollector];
            },
            'filesystem_stream' => static fn(): array => [new FilesystemStreamCollector()],
            'http_stream' => static fn(): array => [new HttpStreamCollector()],
            'command' => static fn(): array => [
                new CommandCollector($timeline),
                new ConsoleAppInfoCollector($timeline, 'Yii2'),
            ],
            'db' => static fn(): array => [new DatabaseCollector($timeline)],
            'mailer' => static fn(): array => [new MailerCollector($timeline)],
            'assets' => static fn(): array => [new AssetBundleCollector($timeline)],
            'environment' => static fn(): array => [new EnvironmentCollector()],
            'cache' => static fn(): array => [new CacheCollector($timeline)],
            'router' => static fn(): array => [new RouterCollector()],
            'queue' => static fn(): array => [new QueueCollector($timeline)],
            'validator' => static fn(): array => [new ValidatorCollector()],
            'translator' => static fn(): array => [new TranslatorCollector()],
            'security' => static fn(): array => [new AuthorizationCollector()],
            'opentelemetry' => static fn(): array => [new OpenTelemetryCollector($timeline)],
        ];
    }

    private function buildDebugger(): void
    {
        $this->debugger = new Debugger(
            \Yii::$container->get(DebuggerIdGenerator::class),
            \Yii::$container->get(StorageInterface::class),
            $this->collectorInstances,
            new DebuggerIgnoreConfig($this->ignoredRequests, $this->ignoredCommands),
        );

        \Yii::$container->setSingleton(Debugger::class, $this->debugger);
    }

    private function registerEventListeners(Application $app): void
    {
        $this->registerApplicationListeners($app);
        $this->registerCollectorProfiling($app);
        $this->registerAuthorizationListeners($app);
    }

    private function registerApplicationListeners(Application $app): void
    {
        if ($app instanceof WebApplication) {
            $this->registerWebListeners($app);
        }

        if ($app instanceof ConsoleApplication) {
            $this->registerConsoleListeners($app);
        }
    }

    private function registerWebListeners(WebApplication $app): void
    {
        $listener = new WebListener(
            $this->debugger,
            $this->getCollector(RequestCollector::class),
            $this->getCollector(WebAppInfoCollector::class),
            $this->getCollector(ExceptionCollector::class),
            $this->getCollector(RouterCollector::class),
            $this->matchRecorder,
        );

        Event::on(WebApplication::class, WebApplication::EVENT_BEFORE_REQUEST, [$listener, 'onBeforeRequest']);
        Event::on(WebApplication::class, WebApplication::EVENT_AFTER_REQUEST, [$listener, 'onAfterRequest']);

        // Hook into exception handling: Yii2's ErrorHandler calls exit(1) after rendering,
        // so EVENT_AFTER_REQUEST never fires. We wrap handleException to capture the exception
        // and flush debug data before exit.
        $this->hookErrorHandler($app, $listener);
    }

    private function registerConsoleListeners(ConsoleApplication $app): void
    {
        $listener = new ConsoleListener(
            $this->debugger,
            $this->getCollector(CommandCollector::class),
            $this->getCollector(ConsoleAppInfoCollector::class),
            $this->getCollector(ExceptionCollector::class),
        );

        Event::on(ConsoleApplication::class, ConsoleApplication::EVENT_BEFORE_REQUEST, [$listener, 'onBeforeRequest']);
        Event::on(ConsoleApplication::class, ConsoleApplication::EVENT_AFTER_REQUEST, [$listener, 'onAfterRequest']);
    }

    private function registerCollectorProfiling(Application $app): void
    {
        // Register event profiling if EventCollector is active
        if ($this->getCollector(EventCollector::class) !== null) {
            $this->registerEventProfiling();
        }

        // Decorate PSR-18 HTTP client with proxy if HttpClientCollector is active
        $httpClientCollector = $this->getCollector(HttpClientCollector::class);
        if ($httpClientCollector instanceof HttpClientCollector) {
            $this->decorateHttpClient($httpClientCollector);
        }

        // Register DB profiling if DatabaseCollector is active
        if ($this->getCollector(DatabaseCollector::class) !== null) {
            $this->registerDbProfiling();
        }

        // Register mailer profiling if MailerCollector is active
        if ($this->getCollector(MailerCollector::class) !== null) {
            $this->registerMailerProfiling();
        }

        // Register asset bundle profiling (web only) if AssetBundleCollector is active
        if ($app instanceof WebApplication && $this->getCollector(AssetBundleCollector::class) !== null) {
            $this->registerAssetProfiling();
        }

        // Register real-time log target if LogCollector is active
        $logCollector = $this->getCollector(LogCollector::class);
        if ($logCollector instanceof LogCollector) {
            $this->registerDebugLogTarget($logCollector);
        }

        // Register translator profiling if TranslatorCollector is active
        $translatorCollector = $this->getCollector(TranslatorCollector::class);
        if ($translatorCollector instanceof TranslatorCollector) {
            $this->registerTranslatorProfiling($app, $translatorCollector);
        }
    }

    private function registerAuthorizationListeners(Application $app): void
    {
        $securityCollector = $this->getCollector(AuthorizationCollector::class);
        if (!$securityCollector instanceof AuthorizationCollector) {
            return;
        }

        if (!$app instanceof WebApplication) {
            return;
        }

        $listener = new \AppDevPanel\Adapter\Yii2\EventListener\AuthorizationListener($securityCollector);
        $listener->register();

        // Collect current user after request initialization
        Event::on(WebApplication::class, WebApplication::EVENT_BEFORE_REQUEST, static function () use (
            $app,
            $listener,
        ): void {
            if ($app->has('user')) {
                $listener->collectCurrentUser($app->getUser());
            }
        });
    }

    private function hookErrorHandler(WebApplication $app, WebListener $listener): void
    {
        $exceptionCollector = $this->getCollector(ExceptionCollector::class);
        if (!$exceptionCollector instanceof ExceptionCollector) {
            return;
        }

        // Yii2's ErrorHandler clears $this->exception after rendering.
        // We intercept by setting a custom exception handler that feeds
        // ExceptionCollector BEFORE Yii2's handler processes (and clears) it.
        $previousHandler = set_exception_handler(null);
        restore_exception_handler();

        $debugger = $this->debugger;
        set_exception_handler(static function (\Throwable $exception) use (
            $exceptionCollector,
            $debugger,
            $previousHandler,
            $listener,
            $app,
        ): void {
            // Feed the collector before Yii2's handler clears the exception
            $exceptionCollector->collect($exception);

            // Extract route data and flush logs that onAfterRequest would have done.
            // EVENT_AFTER_REQUEST never fires when exceptions propagate to the error handler.
            $listener->onExceptionHandler($app);

            // Add X-Debug-Id header before Yii2 renders error response
            if (!headers_sent()) {
                header('X-Debug-Id: ' . $debugger->getId());
            }

            // Delegate to Yii2's error handler
            if ($previousHandler !== null) {
                $previousHandler($exception);
            }
        });
    }

    private function registerConsoleCommands(Application $app): void
    {
        if (!$app instanceof ConsoleApplication) {
            return;
        }

        $collectorRepository = \Yii::$container->get(CollectorRepositoryInterface::class);
        $app->controllerMap['debug-query'] = new DebugQueryController('debug-query', $app, $collectorRepository);

        $storage = \Yii::$container->get(StorageInterface::class);
        $debugger = \Yii::$container->get(Debugger::class);
        $app->controllerMap['debug-reset'] = new DebugResetController('debug-reset', $app, $storage, $debugger);
    }

    private function decorateHttpClient(HttpClientCollector $collector): void
    {
        if (!\Yii::$container->has(ClientInterface::class)) {
            return;
        }

        $originalClient = \Yii::$container->get(ClientInterface::class);
        $proxy = new HttpClientInterfaceProxy($originalClient, $collector);
        \Yii::$container->setSingleton(ClientInterface::class, $proxy);
    }

    private function registerEventProfiling(): void
    {
        /** @var EventCollector|null $eventCollector */
        $eventCollector = $this->getCollector(EventCollector::class);
        if ($eventCollector === null) {
            return;
        }

        // Yii 2 wildcard event listener (requires >= 2.0.14).
        // Captures ALL class-level and instance-level events.
        Event::on('*', '*', static function (\yii\base\Event $event) use ($eventCollector): void {
            $senderClass = is_object($event->sender) ? $event->sender::class : (string) $event->sender;

            // Wrap Yii2 Event into a simple DTO for EventCollector (expects object $event)
            $eventCollector->collect($event, $senderClass . '::' . $event->name);
        });
    }

    private function registerDbProfiling(): void
    {
        /** @var DatabaseCollector|null $dbCollector */
        $dbCollector = $this->getCollector(DatabaseCollector::class);
        if ($dbCollector === null) {
            return;
        }

        // Capture DB queries via Logger profiling messages.
        // Yii 2's Command uses Yii::beginProfile()/endProfile() for query timing,
        // which writes to the Logger. We register a log target that intercepts these
        // profiling messages and feeds them to DatabaseCollector.
        $target = new DbProfilingTarget($dbCollector);
        \Yii::$app->log->targets['adp-db-profiling'] = $target;
    }

    private function registerMailerProfiling(): void
    {
        /** @var MailerCollector|null $mailerCollector */
        $mailerCollector = $this->getCollector(MailerCollector::class);
        if ($mailerCollector === null) {
            return;
        }

        Event::on(
            \yii\mail\BaseMailer::class,
            \yii\mail\BaseMailer::EVENT_AFTER_SEND,
            static function (\yii\mail\MailEvent $event) use ($mailerCollector): void {
                $message = $event->message;
                $normalized = [
                    'from' => self::normalizeAddresses($message->getFrom()),
                    'to' => self::normalizeAddresses($message->getTo()),
                    'cc' => self::normalizeAddresses($message->getCc()),
                    'bcc' => self::normalizeAddresses($message->getBcc()),
                    'replyTo' => self::normalizeAddresses($message->getReplyTo()),
                    'subject' => $message->getSubject(),
                    'textBody' => method_exists($message, 'getTextBody') ? $message->getTextBody() : null,
                    'htmlBody' => method_exists($message, 'getHtmlBody') ? $message->getHtmlBody() : null,
                    'raw' => method_exists($message, 'toString') ? $message->toString() : '',
                    'charset' => $message->getCharset(),
                    'date' => date('r'),
                ];
                $mailerCollector->collectMessage($normalized);
            },
        );
    }

    /**
     * Normalize mail addresses from Yii 2 format to a simple associative array.
     *
     * @return array<string, string>
     */
    private static function normalizeAddresses(mixed $addresses): array
    {
        if ($addresses === null) {
            return [];
        }

        if (is_string($addresses)) {
            return [$addresses => ''];
        }

        if (is_array($addresses)) {
            $result = [];
            foreach ($addresses as $key => $value) {
                if (is_int($key)) {
                    $result[(string) $value] = '';
                } else {
                    $result[$key] = (string) $value;
                }
            }
            return $result;
        }

        return [(string) $addresses => ''];
    }

    private function registerTranslatorProfiling(Application $app, TranslatorCollector $collector): void
    {
        $i18n = $app->getI18n();
        $proxy = new I18NProxy();

        // Copy existing translations config from the original I18N
        $proxy->translations = $i18n->translations;
        $proxy->setCollector($collector);

        $app->set('i18n', $proxy);
    }

    private function registerAssetProfiling(): void
    {
        /** @var AssetBundleCollector|null $assetCollector */
        $assetCollector = $this->getCollector(AssetBundleCollector::class);
        if ($assetCollector === null) {
            return;
        }

        Event::on(\yii\web\View::class, \yii\web\View::EVENT_END_PAGE, static function (\yii\base\Event $event) use (
            $assetCollector,
        ): void {
            /** @var \yii\web\View $view */
            $view = $event->sender;
            if ($view->assetBundles === []) {
                return;
            }

            $normalized = [];
            foreach ($view->assetBundles as $name => $bundle) {
                $normalized[$name] = [
                    'class' => $bundle::class,
                    'sourcePath' => $bundle->sourcePath,
                    'basePath' => $bundle->basePath,
                    'baseUrl' => $bundle->baseUrl,
                    'css' => $bundle->css,
                    'js' => $bundle->js,
                    'depends' => $bundle->depends,
                    'options' => array_filter([
                        'cssOptions' => $bundle->cssOptions,
                        'jsOptions' => $bundle->jsOptions,
                        'publishOptions' => $bundle->publishOptions,
                    ]),
                ];
            }

            $assetCollector->collectBundles($normalized);
        });
    }

    private function registerDebugLogTarget(LogCollector $logCollector): void
    {
        $target = new DebugLogTarget($logCollector);
        \Yii::$app->log->targets['adp-debug'] = $target;
    }

    /**
     * Wrap UrlManager rules with UrlRuleProxy to intercept route matching.
     *
     * Must be called after registerRoutes() so ADP's own rules are also wrapped.
     * The recorder captures which rule matched and how long matching took.
     */
    private function wrapUrlRules(Application $app): void
    {
        if (!$app instanceof WebApplication) {
            return;
        }

        if (!($this->collectors['router'] ?? true)) {
            return;
        }

        $this->matchRecorder = new RouterMatchRecorder();
        $urlManager = $app->getUrlManager();

        $wrappedRules = [];
        foreach ($urlManager->rules as $rule) {
            $wrappedRules[] = new UrlRuleProxy($rule, $this->matchRecorder);
        }
        $urlManager->rules = $wrappedRules;
    }

    private function registerRoutes(Application $app): void
    {
        if (!$app instanceof WebApplication) {
            return;
        }

        $app->getUrlManager()->addRules(
            [
                // API routes (must be before the panel catch-all)
                [
                    'class' => \yii\web\UrlRule::class,
                    'pattern' => 'debug/api/<path:.*>',
                    'route' => 'debug-panel/adp-api/handle',
                    'defaults' => ['path' => ''],
                ],
                [
                    'class' => \yii\web\UrlRule::class,
                    'pattern' => 'debug/api',
                    'route' => 'debug-panel/adp-api/handle',
                ],
                [
                    'class' => \yii\web\UrlRule::class,
                    'pattern' => 'inspect/api/<path:.*>',
                    'route' => 'debug-panel/adp-api/handle',
                    'defaults' => ['path' => ''],
                ],
                [
                    'class' => \yii\web\UrlRule::class,
                    'pattern' => 'inspect/api',
                    'route' => 'debug-panel/adp-api/handle',
                ],
                // Panel SPA routes (catch-all for client-side routing)
                [
                    'class' => \yii\web\UrlRule::class,
                    'pattern' => 'debug/<path:(?!api(/|$)).+>',
                    'route' => 'debug-panel/adp-api/handle',
                    'defaults' => ['path' => ''],
                    'verb' => ['GET'],
                ],
                [
                    'class' => \yii\web\UrlRule::class,
                    'pattern' => 'debug',
                    'route' => 'debug-panel/adp-api/handle',
                    'verb' => ['GET'],
                ],
            ],
            false,
        );
    }
}
