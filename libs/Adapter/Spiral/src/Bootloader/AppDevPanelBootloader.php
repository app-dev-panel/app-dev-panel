<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Bootloader;

use AppDevPanel\Adapter\Spiral\Controller\AdpApiController;
use AppDevPanel\Adapter\Spiral\Middleware\AdpApiMiddleware;
use AppDevPanel\Adapter\Spiral\Middleware\DebugMiddleware;
use AppDevPanel\Api\ApiApplication;
use AppDevPanel\Api\Debug\Middleware\ResponseDataWrapper;
use AppDevPanel\Api\Debug\Repository\CollectorRepository;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Http\JsonResponseFactory;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Inspector\Authorization\AuthorizationConfigProviderInterface;
use AppDevPanel\Api\Inspector\Authorization\NullAuthorizationConfigProvider;
use AppDevPanel\Api\Inspector\Database\NullSchemaProvider;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use AppDevPanel\Api\Inspector\Elasticsearch\ElasticsearchProviderInterface;
use AppDevPanel\Api\Inspector\Elasticsearch\NullElasticsearchProvider;
use AppDevPanel\Api\Inspector\HttpMock\HttpMockProviderInterface;
use AppDevPanel\Api\Inspector\HttpMock\NullHttpMockProvider;
use AppDevPanel\Api\NullPathMapper;
use AppDevPanel\Api\Panel\PanelConfig;
use AppDevPanel\Api\Panel\PanelController;
use AppDevPanel\Api\PathMapperInterface;
use AppDevPanel\Api\PathResolver;
use AppDevPanel\Api\PathResolverInterface;
use AppDevPanel\Api\Toolbar\ToolbarConfig;
use AppDevPanel\Api\Toolbar\ToolbarInjector;
use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\LoggerInterfaceProxy;
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\RouterCollector;
use AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector;
use AppDevPanel\Kernel\Collector\TemplateCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use AppDevPanel\Kernel\Collector\ValidatorCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\DebuggerIgnoreConfig;
use AppDevPanel\Kernel\Storage\FileStorage;
use AppDevPanel\Kernel\Storage\StorageInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Spiral\Boot\Bootloader\Bootloader;

/**
 * Spiral Bootloader that wires all ADP services into the Spiral container.
 *
 * Register this bootloader in your Spiral application's Kernel:
 *
 *     public function defineBootloaders(): array
 *     {
 *         return [
 *             ...
 *             \AppDevPanel\Adapter\Spiral\Bootloader\AppDevPanelBootloader::class,
 *         ];
 *     }
 *
 * Then attach the `DebugMiddleware` to your HTTP pipeline (outermost, before CSRF/sessions)
 * and the `AdpApiMiddleware` in front of your router so `/debug/*` and `/inspect/api/*`
 * hand off to ADP before reaching the application routes.
 */
final class AppDevPanelBootloader extends Bootloader
{
    protected const SINGLETONS = [
        // PSR-17 factories — nyholm/psr7 implementations.
        ResponseFactoryInterface::class => [self::class, 'initPsr17Factory'],
        ServerRequestFactoryInterface::class => [self::class, 'initPsr17Factory'],
        StreamFactoryInterface::class => [self::class, 'initPsr17Factory'],
        UploadedFileFactoryInterface::class => [self::class, 'initPsr17Factory'],
        UriFactoryInterface::class => [self::class, 'initPsr17Factory'],

        // Kernel infrastructure
        DebuggerIdGenerator::class => DebuggerIdGenerator::class,
        StorageInterface::class => [self::class, 'initStorage'],
        Debugger::class => [self::class, 'initDebugger'],

        // Collectors — each class must implement CollectorInterface
        RequestCollector::class => RequestCollector::class,
        WebAppInfoCollector::class => WebAppInfoCollector::class,
        LogCollector::class => LogCollector::class,
        EventCollector::class => EventCollector::class,
        ExceptionCollector::class => ExceptionCollector::class,
        HttpClientCollector::class => HttpClientCollector::class,
        VarDumperCollector::class => VarDumperCollector::class,
        TimelineCollector::class => TimelineCollector::class,
        FilesystemStreamCollector::class => FilesystemStreamCollector::class,
        CacheCollector::class => CacheCollector::class,
        RouterCollector::class => RouterCollector::class,
        ValidatorCollector::class => ValidatorCollector::class,
        TranslatorCollector::class => TranslatorCollector::class,
        TemplateCollector::class => TemplateCollector::class,
        MailerCollector::class => MailerCollector::class,
        QueueCollector::class => QueueCollector::class,
        DatabaseCollector::class => DatabaseCollector::class,

        // API
        CollectorRepositoryInterface::class => [self::class, 'initCollectorRepository'],
        JsonResponseFactoryInterface::class => JsonResponseFactory::class,
        PathMapperInterface::class => NullPathMapper::class,
        PathResolverInterface::class => [self::class, 'initPathResolver'],
        SchemaProviderInterface::class => NullSchemaProvider::class,
        AuthorizationConfigProviderInterface::class => NullAuthorizationConfigProvider::class,
        ElasticsearchProviderInterface::class => NullElasticsearchProvider::class,
        HttpMockProviderInterface::class => NullHttpMockProvider::class,
        PanelConfig::class => [self::class, 'initPanelConfig'],
        PanelController::class => PanelController::class,
        ToolbarConfig::class => ToolbarConfig::class,
        ToolbarInjector::class => ToolbarInjector::class,
        ResponseDataWrapper::class => ResponseDataWrapper::class,
        ApiApplication::class => [self::class, 'initApiApplication'],

        // Adapter glue
        AdpApiController::class => AdpApiController::class,
        AdpApiMiddleware::class => AdpApiMiddleware::class,
        DebugMiddleware::class => DebugMiddleware::class,

        // PSR-3 logger — fall back to NullLogger if the app hasn't bound one.
        LoggerInterface::class => [self::class, 'initLogger'],
    ];

    public function initPsr17Factory(): Psr17Factory
    {
        return new Psr17Factory();
    }

    /**
     * Build PanelConfig honouring `APP_DEV_PANEL_STATIC_URL` (so the playground can point
     * the panel SPA at a locally-served `/panel-dist` instead of the flaky GitHub Pages CDN).
     */
    public function initPanelConfig(): PanelConfig
    {
        $staticUrl = getenv('APP_DEV_PANEL_STATIC_URL');
        if (is_string($staticUrl) && $staticUrl !== '') {
            return new PanelConfig(staticUrl: $staticUrl);
        }

        return new PanelConfig();
    }

    /**
     * Build the PathResolver with project + runtime paths. Resolved from
     * `APP_DEV_PANEL_ROOT_PATH` / `APP_DEV_PANEL_RUNTIME_PATH` env vars when set
     * (Spiral apps typically expose `directories('root')` / `directories('runtime')`
     * via their bootloaders — bind your own `PathResolverInterface` factory if you
     * want to feed those values directly). Otherwise falls back to CWD-relative
     * defaults that work for `php -S` based playgrounds.
     */
    public function initPathResolver(): PathResolver
    {
        $rootPath = getenv('APP_DEV_PANEL_ROOT_PATH');
        if (!is_string($rootPath) || $rootPath === '') {
            $rootPath = (string) getcwd();
        }

        $runtimePath = getenv('APP_DEV_PANEL_RUNTIME_PATH');
        if (!is_string($runtimePath) || $runtimePath === '') {
            $runtimePath = $rootPath . '/var';
        }

        return new PathResolver($rootPath, $runtimePath);
    }

    public function initLogger(ContainerInterface $container): LoggerInterface
    {
        if ($container->has('logger')) {
            /** @var LoggerInterface */
            return $container->get('logger');
        }

        return new NullLogger();
    }

    public function initStorage(DebuggerIdGenerator $idGenerator): StorageInterface
    {
        $path = $this->resolveStoragePath();
        if (!is_dir($path)) {
            mkdir($path, 0o777, true);
        }

        return new FileStorage($path, $idGenerator);
    }

    /**
     * Build the Debugger with all registered collectors.
     *
     * Collectors may decorate PSR services (logger, event dispatcher, http client) —
     * the decoration is applied when this bootloader's boot() runs the singleton factory.
     */
    public function initDebugger(
        ContainerInterface $container,
        DebuggerIdGenerator $idGenerator,
        StorageInterface $storage,
    ): Debugger {
        $collectors = [];
        foreach ($this->collectorClasses() as $class) {
            if (!$container->has($class)) {
                continue;
            }

            $instance = $container->get($class);
            if ($instance instanceof CollectorInterface) {
                $collectors[] = $instance;
            }
        }

        return new Debugger($idGenerator, $storage, $collectors, new DebuggerIgnoreConfig([], []));
    }

    public function initCollectorRepository(StorageInterface $storage): CollectorRepositoryInterface
    {
        return new CollectorRepository($storage);
    }

    public function initApiApplication(
        ContainerInterface $container,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ): ApiApplication {
        return new ApiApplication($container, $responseFactory, $streamFactory);
    }

    /**
     * Decorates PSR services with ADP proxies so collectors receive their events.
     *
     * Called by the Spiral Kernel after all bootloaders have been initialized.
     */
    public function boot(ContainerInterface $container): void
    {
        $this->decorateLogger($container);
        $this->decorateEventDispatcher($container);
        $this->decorateHttpClient($container);
    }

    /**
     * @return list<class-string<CollectorInterface>>
     */
    private function collectorClasses(): array
    {
        return [
            WebAppInfoCollector::class,
            RequestCollector::class,
            LogCollector::class,
            EventCollector::class,
            ExceptionCollector::class,
            HttpClientCollector::class,
            VarDumperCollector::class,
            TimelineCollector::class,
            FilesystemStreamCollector::class,
            CacheCollector::class,
            RouterCollector::class,
            ValidatorCollector::class,
            TranslatorCollector::class,
            TemplateCollector::class,
            MailerCollector::class,
            QueueCollector::class,
            DatabaseCollector::class,
        ];
    }

    private function decorateLogger(ContainerInterface $container): void
    {
        if (!$container->has(LoggerInterface::class) || !$container->has(LogCollector::class)) {
            return;
        }

        if (!$container instanceof \Spiral\Core\Container) {
            return;
        }

        $original = $container->get(LoggerInterface::class);
        $collector = $container->get(LogCollector::class);
        $container->bindSingleton(LoggerInterface::class, new LoggerInterfaceProxy($original, $collector));
    }

    private function decorateEventDispatcher(ContainerInterface $container): void
    {
        if (
            !$container->has(EventDispatcherInterface::class)
            || !$container->has(EventCollector::class)
            || !$container instanceof \Spiral\Core\Container
        ) {
            return;
        }

        $original = $container->get(EventDispatcherInterface::class);
        $collector = $container->get(EventCollector::class);
        $container->bindSingleton(
            EventDispatcherInterface::class,
            new EventDispatcherInterfaceProxy($original, $collector),
        );
    }

    private function decorateHttpClient(ContainerInterface $container): void
    {
        if (
            !$container->has(ClientInterface::class)
            || !$container->has(HttpClientCollector::class)
            || !$container instanceof \Spiral\Core\Container
        ) {
            return;
        }

        $original = $container->get(ClientInterface::class);
        $collector = $container->get(HttpClientCollector::class);
        $container->bindSingleton(ClientInterface::class, new HttpClientInterfaceProxy($original, $collector));
    }

    private function resolveStoragePath(): string
    {
        $envPath = getenv('APP_DEV_PANEL_STORAGE_PATH');
        if (is_string($envPath) && $envPath !== '') {
            return $envPath;
        }

        return sys_get_temp_dir() . '/app-dev-panel';
    }
}
