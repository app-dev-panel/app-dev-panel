<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Bootloader;

use AppDevPanel\Adapter\Spiral\Container\EventDispatcherProxyInjector;
use AppDevPanel\Adapter\Spiral\Container\HttpClientProxyInjector;
use AppDevPanel\Adapter\Spiral\Container\LoggerProxyInjector;
use AppDevPanel\Adapter\Spiral\Controller\AdpApiController;
use AppDevPanel\Adapter\Spiral\Inspector\SpiralAuthorizationConfigProvider;
use AppDevPanel\Adapter\Spiral\Inspector\SpiralConfigProvider;
use AppDevPanel\Adapter\Spiral\Inspector\SpiralEventListenerProvider;
use AppDevPanel\Adapter\Spiral\Inspector\SpiralRouteCollectionAdapter;
use AppDevPanel\Adapter\Spiral\Inspector\SpiralUrlMatcherAdapter;
use AppDevPanel\Adapter\Spiral\Middleware\AdpApiMiddleware;
use AppDevPanel\Adapter\Spiral\Middleware\DebugMiddleware;
use AppDevPanel\Api\ApiApplication;
use AppDevPanel\Api\Debug\Middleware\ResponseDataWrapper;
use AppDevPanel\Api\Debug\Repository\CollectorRepository;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Http\JsonResponseFactory;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Inspector\Authorization\AuthorizationConfigProviderInterface;
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
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
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
use Spiral\Core\BinderInterface;

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
        AuthorizationConfigProviderInterface::class => SpiralAuthorizationConfigProvider::class,
        SpiralAuthorizationConfigProvider::class => SpiralAuthorizationConfigProvider::class,
        SpiralEventListenerProvider::class => SpiralEventListenerProvider::class,
        SpiralConfigProvider::class => [self::class, 'initConfigProvider'],
        'config' => SpiralConfigProvider::class,
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

        // Container injectors that wrap PSR services with collector-aware proxies.
        // Registered as singletons so the bootloader can capture the original
        // application binding via `setUnderlying()` before swapping in the injector.
        LoggerProxyInjector::class => LoggerProxyInjector::class,
        EventDispatcherProxyInjector::class => EventDispatcherProxyInjector::class,
        HttpClientProxyInjector::class => HttpClientProxyInjector::class,
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
     * Build the inspector `'config'` provider with whatever Spiral introspection sources
     * the application happens to expose. Each constructor argument is optional — apps that
     * don't bind `EnvironmentInterface` / `DirectoriesInterface` / `InitializerInterface`
     * (the playground does not run the full `AbstractKernel`, for example) still get a
     * provider that returns useful data for the groups it can serve.
     */
    public function initConfigProvider(ContainerInterface $container): SpiralConfigProvider
    {
        if (!$container instanceof \Spiral\Core\Container) {
            // No-op provider on a non-Spiral container — every group resolves to [].
            return new SpiralConfigProvider(new \Spiral\Core\Container());
        }

        $env = null;
        if ($container->has(\Spiral\Boot\EnvironmentInterface::class)) {
            $candidate = $container->get(\Spiral\Boot\EnvironmentInterface::class);
            if ($candidate instanceof \Spiral\Boot\EnvironmentInterface) {
                $env = $candidate;
            }
        }

        $dirs = null;
        if ($container->has(\Spiral\Boot\DirectoriesInterface::class)) {
            $candidate = $container->get(\Spiral\Boot\DirectoriesInterface::class);
            if ($candidate instanceof \Spiral\Boot\DirectoriesInterface) {
                $dirs = $candidate;
            }
        }

        $initializer = null;
        if ($container->has(\Spiral\Boot\BootloadManager\InitializerInterface::class)) {
            $candidate = $container->get(\Spiral\Boot\BootloadManager\InitializerInterface::class);
            if ($candidate instanceof \Spiral\Boot\BootloadManager\InitializerInterface) {
                $initializer = $candidate;
            }
        }

        $events = null;
        if ($container->has(SpiralEventListenerProvider::class)) {
            $candidate = $container->get(SpiralEventListenerProvider::class);
            if ($candidate instanceof SpiralEventListenerProvider) {
                $events = $candidate;
            }
        }

        return new SpiralConfigProvider($container, $env, $dirs, $initializer, $events);
    }

    /**
     * Wires PSR services through the Spiral container's `bindInjector` mechanism so
     * every resolution of `LoggerInterface` / `EventDispatcherInterface` / `ClientInterface`
     * yields a collector-aware proxy.
     *
     * For each interface this method:
     *   1. Captures the application's currently bound instance (Monolog, Symfony's
     *      EventDispatcher, Guzzle, …) — the upcoming `bindInjector` call will overwrite
     *      that binding, so the original must be stashed on the injector first.
     *   2. Registers the injector class name with the binder. Spiral resolves and invokes
     *      `createInjection()` on every subsequent `$container->get($iface)` call,
     *      returning a fresh proxy wrapping the captured underlying.
     *
     * Called by the Spiral Kernel after all bootloaders have been initialized.
     */
    public function boot(ContainerInterface $container): void
    {
        if (!$container instanceof \Spiral\Core\Container) {
            return;
        }

        $binder = $container->getBinder();

        $this->installInjector($container, $binder, LoggerInterface::class, LoggerProxyInjector::class);
        $this->installInjector(
            $container,
            $binder,
            EventDispatcherInterface::class,
            EventDispatcherProxyInjector::class,
        );
        $this->installInjector($container, $binder, ClientInterface::class, HttpClientProxyInjector::class);

        $this->installRouterAdapters($container);
    }

    /**
     * Wire the inspector's `'router'` / `'urlMatcher'` aliases when `spiral/router`
     * is installed and the application has bound a {@see \Spiral\Router\RouterInterface}.
     *
     * The aliases are duck-typed by {@see \AppDevPanel\Api\Inspector\Controller\RoutingController}:
     *   - `'router'`     → `getRoutes(): list<object>` where each route exposes `__debugInfo()`
     *   - `'urlMatcher'` → `match(ServerRequestInterface): SpiralMatchResult`
     *
     * Both are conditional — without `spiral/router`, the inspector controller falls back
     * to its built-in 501 "requires framework integration" response, which is the contract
     * for apps using a custom router (e.g. the ADP playground's `PathRouter`).
     */
    private function installRouterAdapters(\Spiral\Core\Container $container): void
    {
        if (!interface_exists(\Spiral\Router\RouterInterface::class)) {
            return;
        }
        if (!$container->has(\Spiral\Router\RouterInterface::class)) {
            return;
        }

        $router = $container->get(\Spiral\Router\RouterInterface::class);
        if (!$router instanceof \Spiral\Router\RouterInterface) {
            return;
        }

        $container->bindSingleton('router', new SpiralRouteCollectionAdapter($router));
        $container->bindSingleton('urlMatcher', new SpiralUrlMatcherAdapter($router));
    }

    /**
     * @param class-string $interface
     * @param class-string $injectorClass
     */
    private function installInjector(
        ContainerInterface $container,
        BinderInterface $binder,
        string $interface,
        string $injectorClass,
    ): void {
        if (!$container->has($injectorClass)) {
            return;
        }

        $injector = $container->get($injectorClass);
        if (!is_object($injector) || !method_exists($injector, 'setUnderlying')) {
            return;
        }

        // Capture the application's currently-bound instance BEFORE we overwrite the
        // binding via `bindInjector`. Spiral has no notion of "previous binding" — the
        // injector's `Injectable` config replaces whatever `Shared`/`Factory` config
        // was there, so the original reference would otherwise be lost.
        if ($container->has($interface)) {
            $underlying = $container->get($interface);
            if (is_object($underlying)) {
                $injector->setUnderlying($underlying);
            }

            // Resolving the singleton above caches it in the binder's state.
            // `bindInjector` ultimately calls `setBinding`, which refuses to overwrite
            // a constructed singleton — so we must drop the cached binding first.
            $binder->removeBinding($interface);
        }

        $binder->bindInjector($interface, $injectorClass);
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

    private function resolveStoragePath(): string
    {
        $envPath = getenv('APP_DEV_PANEL_STORAGE_PATH');
        if (is_string($envPath) && $envPath !== '') {
            return $envPath;
        }

        return sys_get_temp_dir() . '/app-dev-panel';
    }
}
