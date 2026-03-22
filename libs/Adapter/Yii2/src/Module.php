<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2;

use AppDevPanel\Adapter\Yii2\Collector\DbProfilingTarget;
use AppDevPanel\Adapter\Yii2\Collector\DebugLogTarget;
use AppDevPanel\Adapter\Yii2\Controller\AdpApiController;
use AppDevPanel\Adapter\Yii2\Controller\DebugQueryController;
use AppDevPanel\Adapter\Yii2\Controller\DebugResetController;
use AppDevPanel\Adapter\Yii2\EventListener\ConsoleListener;
use AppDevPanel\Adapter\Yii2\EventListener\WebListener;
use AppDevPanel\Adapter\Yii2\Inspector\NullSchemaProvider;
use AppDevPanel\Adapter\Yii2\Inspector\Yii2ConfigProvider;
use AppDevPanel\Adapter\Yii2\Inspector\Yii2DbSchemaProvider;
use AppDevPanel\Adapter\Yii2\Inspector\Yii2RouteCollection;
use AppDevPanel\Api\ApiApplication;
use AppDevPanel\Api\Debug\Middleware\ResponseDataWrapper;
use AppDevPanel\Api\Debug\Repository\CollectorRepository;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Http\JsonResponseFactory;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
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
use AppDevPanel\Api\Middleware\IpFilterMiddleware;
use AppDevPanel\Api\PathResolver;
use AppDevPanel\Api\PathResolverInterface;
use AppDevPanel\Kernel\Collector\AssetBundleCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector;
use AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
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
    public int $historySize = 50;

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
        'filesystem_stream' => true,
        'http_stream' => true,
        'command' => true,
        'db' => true,
        'mailer' => true,
        'assets' => true,
    ];

    /**
     * @var string[] URL patterns to ignore (wildcard).
     */
    public array $ignoredRequests = ['/debug/api/*', '/inspect/api/*'];

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

    public $controllerNamespace = 'AppDevPanel\\Adapter\\Yii2\\Controller';

    private ?Debugger $debugger = null;
    private ?TimelineCollector $timelineCollector = null;

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
        $this->registerEventListeners($app);
        $this->registerRoutes($app);
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

        $idGenerator = new DebuggerIdGenerator();
        $storage = new FileStorage($storagePath, $idGenerator, $this->excludedClasses);

        $httpFactory = new HttpFactory();

        \Yii::$container->setSingleton(DebuggerIdGenerator::class, $idGenerator);
        \Yii::$container->setSingleton(StorageInterface::class, $storage);
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

        // Schema provider
        if (isset(\Yii::$app->db)) {
            \Yii::$container->setSingleton(
                SchemaProviderInterface::class,
                static fn() => new Yii2DbSchemaProvider(\Yii::$app->db),
            );
        } else {
            \Yii::$container->setSingleton(SchemaProviderInterface::class, NullSchemaProvider::class);
        }

        // Config provider
        \Yii::$container->setSingleton(Yii2ConfigProvider::class, static fn() => new Yii2ConfigProvider(\Yii::$app));
        \Yii::$container->set('config', Yii2ConfigProvider::class);

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

        // API Application
        \Yii::$container->setSingleton(ApiApplication::class, static function () use ($containerBridge) {
            return new ApiApplication(
                $containerBridge,
                \Yii::$container->get(ResponseFactoryInterface::class),
                \Yii::$container->get(StreamFactoryInterface::class),
            );
        });

        // Inspector controllers — explicit registration to avoid auto-wiring issues.
        // Each adapter must register these (same pattern as Yiisoft/Symfony adapters).
        \Yii::$container->setSingleton(
            FileController::class,
            static fn() => new FileController(
                \Yii::$container->get(JsonResponseFactoryInterface::class),
                \Yii::$container->get(PathResolverInterface::class),
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

        $appParams = $app?->params ?? [];
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
                \Yii::$container->get(ClientInterface::class),
                \Yii::$container->get(ResponseFactoryInterface::class),
                \Yii::$container->get(StreamFactoryInterface::class),
            ),
        );

        \Yii::$container->setSingleton(
            TranslationController::class,
            static fn() => new TranslationController(\Yii::$container->get(JsonResponseFactoryInterface::class)),
        );

        \Yii::$container->setSingleton(
            OpcacheController::class,
            static fn() => new OpcacheController(\Yii::$container->get(JsonResponseFactoryInterface::class)),
        );
    }

    private function registerCollectors(): void
    {
        $timeline = $this->getTimelineCollector();
        $this->collectorInstances[] = $timeline;

        $collectors = $this->collectors;

        if ($collectors['request'] ?? true) {
            $this->collectorInstances[] = new RequestCollector($timeline);
            $this->collectorInstances[] = new WebAppInfoCollector($timeline, 'Yii2');
        }

        if ($collectors['exception'] ?? true) {
            $this->collectorInstances[] = new ExceptionCollector($timeline);
        }

        if ($collectors['log'] ?? true) {
            $this->collectorInstances[] = new LogCollector($timeline);
        }

        if ($collectors['event'] ?? true) {
            $this->collectorInstances[] = new EventCollector($timeline);
        }

        if ($collectors['service'] ?? true) {
            $this->collectorInstances[] = new ServiceCollector($timeline);
        }

        if ($collectors['http_client'] ?? true) {
            $this->collectorInstances[] = new HttpClientCollector($timeline);
        }

        if ($collectors['var_dumper'] ?? true) {
            $varDumperCollector = new VarDumperCollector($timeline);
            $this->collectorInstances[] = $varDumperCollector;
            \Yii::$container->setSingleton(VarDumperCollector::class, $varDumperCollector);
        }

        if ($collectors['filesystem_stream'] ?? true) {
            $this->collectorInstances[] = new FilesystemStreamCollector();
        }

        if ($collectors['http_stream'] ?? true) {
            $this->collectorInstances[] = new HttpStreamCollector();
        }

        if ($collectors['command'] ?? true) {
            $this->collectorInstances[] = new CommandCollector($timeline);
            $this->collectorInstances[] = new ConsoleAppInfoCollector($timeline, 'Yii2');
        }

        // Yii2-specific collectors
        if ($collectors['db'] ?? true) {
            $this->collectorInstances[] = new DatabaseCollector($timeline);
        }

        if ($collectors['mailer'] ?? true) {
            $this->collectorInstances[] = new MailerCollector($timeline);
        }

        if ($collectors['assets'] ?? true) {
            $this->collectorInstances[] = new AssetBundleCollector($timeline);
        }
    }

    private function buildDebugger(): void
    {
        $this->debugger = new Debugger(
            \Yii::$container->get(DebuggerIdGenerator::class),
            \Yii::$container->get(StorageInterface::class),
            $this->collectorInstances,
            $this->ignoredRequests,
            $this->ignoredCommands,
        );

        \Yii::$container->setSingleton(Debugger::class, $this->debugger);
    }

    private function registerEventListeners(Application $app): void
    {
        if ($app instanceof WebApplication) {
            $listener = new WebListener(
                $this->debugger,
                $this->getCollector(RequestCollector::class),
                $this->getCollector(WebAppInfoCollector::class),
                $this->getCollector(ExceptionCollector::class),
            );

            Event::on(WebApplication::class, WebApplication::EVENT_BEFORE_REQUEST, [$listener, 'onBeforeRequest']);
            Event::on(WebApplication::class, WebApplication::EVENT_AFTER_REQUEST, [$listener, 'onAfterRequest']);

            // Hook into exception handling: Yii2's ErrorHandler calls exit(1) after rendering,
            // so EVENT_AFTER_REQUEST never fires. We wrap handleException to capture the exception
            // and flush debug data before exit.
            $this->hookErrorHandler($app, $listener);
        }

        if ($app instanceof ConsoleApplication) {
            $listener = new ConsoleListener(
                $this->debugger,
                $this->getCollector(CommandCollector::class),
                $this->getCollector(ConsoleAppInfoCollector::class),
                $this->getCollector(ExceptionCollector::class),
            );

            Event::on(
                ConsoleApplication::class,
                ConsoleApplication::EVENT_BEFORE_REQUEST,
                [$listener, 'onBeforeRequest'],
            );
            Event::on(
                ConsoleApplication::class,
                ConsoleApplication::EVENT_AFTER_REQUEST,
                [$listener, 'onAfterRequest'],
            );
        }

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
        ): void {
            // Feed the collector before Yii2's handler clears the exception
            $exceptionCollector->collect($exception);

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
                    'subject' => $message->getSubject() ?? '',
                    'textBody' => method_exists($message, 'getTextBody') ? $message->getTextBody() : null,
                    'htmlBody' => method_exists($message, 'getHtmlBody') ? $message->getHtmlBody() : null,
                    'raw' => method_exists($message, 'toString') ? (string) $message->toString() : '',
                    'charset' => $message->getCharset() ?? 'utf-8',
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
            if (!property_exists($view, 'assetBundles') || !is_array($view->assetBundles)) {
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

    private function registerRoutes(Application $app): void
    {
        if (!$app instanceof WebApplication) {
            return;
        }

        $app->getUrlManager()->addRules([
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
        ], false);
    }
}
