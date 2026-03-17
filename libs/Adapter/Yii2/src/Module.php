<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2;

use AppDevPanel\Adapter\Yii2\Collector\DbCollector;
use AppDevPanel\Adapter\Yii2\Collector\Yii2LogCollector;
use AppDevPanel\Adapter\Yii2\Controller\AdpApiController;
use AppDevPanel\Adapter\Yii2\EventListener\ConsoleListener;
use AppDevPanel\Adapter\Yii2\EventListener\WebListener;
use AppDevPanel\Adapter\Yii2\Inspector\NullSchemaProvider;
use AppDevPanel\Adapter\Yii2\Inspector\Yii2ConfigProvider;
use AppDevPanel\Adapter\Yii2\Inspector\Yii2DbSchemaProvider;
use AppDevPanel\Api\ApiApplication;
use AppDevPanel\Api\Debug\Repository\CollectorRepository;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Http\JsonResponseFactory;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use AppDevPanel\Api\PathResolver;
use AppDevPanel\Api\PathResolverInterface;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
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
final class Module extends \yii\base\Module implements BootstrapInterface
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
        'yii_log' => true,
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

    public function bootstrap($app): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->registerServices();
        $this->registerCollectors();
        $this->buildDebugger();
        $this->registerEventListeners($app);
        $this->registerRoutes($app);
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

    private function registerServices(): void
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
            \Yii::$container->setSingleton(ClientInterface::class, static fn () => new Client(['timeout' => 10]));
        }

        $basePath = \Yii::getAlias('@app');
        $runtimePath = \Yii::getAlias('@runtime');

        \Yii::$container->setSingleton(PathResolverInterface::class, static fn () => new PathResolver($basePath, $runtimePath));
        \Yii::$container->setSingleton(JsonResponseFactoryInterface::class, static fn () => new JsonResponseFactory(
            \Yii::$container->get(ResponseFactoryInterface::class),
            \Yii::$container->get(StreamFactoryInterface::class),
        ));
        \Yii::$container->setSingleton(ServiceRegistryInterface::class, static fn () => new FileServiceRegistry($storagePath . '/services'));
        \Yii::$container->setSingleton(CollectorRepositoryInterface::class, static fn () => new CollectorRepository(
            \Yii::$container->get(StorageInterface::class),
        ));

        // Schema provider
        if (isset(\Yii::$app->db)) {
            \Yii::$container->setSingleton(SchemaProviderInterface::class, static fn () => new Yii2DbSchemaProvider(\Yii::$app->db));
        } else {
            \Yii::$container->setSingleton(SchemaProviderInterface::class, NullSchemaProvider::class);
        }

        // Config provider
        \Yii::$container->setSingleton(Yii2ConfigProvider::class, static fn () => new Yii2ConfigProvider(\Yii::$app));
        \Yii::$container->set('config', Yii2ConfigProvider::class);

        // API Application
        \Yii::$container->setSingleton(ApiApplication::class, static function () {
            return new ApiApplication(
                new class implements \Psr\Container\ContainerInterface {
                    public function get(string $id): mixed
                    {
                        return \Yii::$container->get($id);
                    }

                    public function has(string $id): bool
                    {
                        return \Yii::$container->has($id);
                    }
                },
                \Yii::$container->get(ResponseFactoryInterface::class),
                \Yii::$container->get(StreamFactoryInterface::class),
            );
        });
    }

    private function registerCollectors(): void
    {
        $timeline = $this->getTimelineCollector();
        $this->collectorInstances[] = $timeline;

        $collectors = $this->collectors;

        if ($collectors['request'] ?? true) {
            $this->collectorInstances[] = new RequestCollector($timeline);
            $this->collectorInstances[] = new WebAppInfoCollector($timeline);
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
            $this->collectorInstances[] = new VarDumperCollector($timeline);
        }

        if ($collectors['filesystem_stream'] ?? true) {
            $this->collectorInstances[] = new FilesystemStreamCollector();
        }

        if ($collectors['http_stream'] ?? true) {
            $this->collectorInstances[] = new HttpStreamCollector();
        }

        if ($collectors['command'] ?? true) {
            $this->collectorInstances[] = new CommandCollector($timeline);
            $this->collectorInstances[] = new ConsoleAppInfoCollector($timeline);
        }

        // Yii2-specific collectors
        if ($collectors['db'] ?? true) {
            $this->collectorInstances[] = new DbCollector($timeline);
        }

        if ($collectors['yii_log'] ?? true) {
            $this->collectorInstances[] = new Yii2LogCollector($timeline);
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

            // Register error handler hook for exceptions
            $app->on('afterAction', static function () use ($listener): void {
                $listener->onAfterAction();
            });
        }

        if ($app instanceof ConsoleApplication) {
            $listener = new ConsoleListener(
                $this->debugger,
                $this->getCollector(CommandCollector::class),
                $this->getCollector(ConsoleAppInfoCollector::class),
                $this->getCollector(ExceptionCollector::class),
            );

            Event::on(ConsoleApplication::class, ConsoleApplication::EVENT_BEFORE_REQUEST, [$listener, 'onBeforeRequest']);
            Event::on(ConsoleApplication::class, ConsoleApplication::EVENT_AFTER_REQUEST, [$listener, 'onAfterRequest']);
        }

        // Register DB profiling if DbCollector is active
        if ($this->getCollector(DbCollector::class) !== null) {
            $this->registerDbProfiling();
        }
    }

    private function registerDbProfiling(): void
    {
        /** @var DbCollector|null $dbCollector */
        $dbCollector = $this->getCollector(DbCollector::class);
        if ($dbCollector === null) {
            return;
        }

        // Hook into Yii2's DB events
        Event::on(\yii\db\Connection::class, \yii\db\Connection::EVENT_AFTER_OPEN, static function () use ($dbCollector): void {
            $dbCollector->logConnection();
        });

        Event::on(\yii\db\Command::class, \yii\db\Command::EVENT_AFTER_EXECUTE, static function (\yii\db\Event $event) use ($dbCollector): void {
            /** @var \yii\db\Command $command */
            $command = $event->sender;
            $dbCollector->logQuery(
                $command->getRawSql(),
                [],
                $command->pdoStatement?->rowCount() ?? 0,
            );
        });
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
