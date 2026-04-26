<?php

declare(strict_types=1);

namespace App\Application;

use App\Controller\TestFixtures\CacheAction;
use App\Controller\TestFixtures\CacheHeavyAction;
use App\Controller\TestFixtures\DatabaseAction;
use App\Controller\TestFixtures\DumpAction;
use App\Controller\TestFixtures\EventsAction;
use App\Controller\TestFixtures\ExceptionAction;
use App\Controller\TestFixtures\ExceptionChainedAction;
use App\Controller\TestFixtures\FilesystemAction;
use App\Controller\TestFixtures\HttpClientAction;
use App\Controller\TestFixtures\LogsAction;
use App\Controller\TestFixtures\LogsContextAction;
use App\Controller\TestFixtures\LogsHeavyAction;
use App\Controller\TestFixtures\MailerAction;
use App\Controller\TestFixtures\MultiAction;
use App\Controller\TestFixtures\NotFoundAction;
use App\Controller\TestFixtures\QueueAction;
use App\Controller\TestFixtures\RequestInfoAction;
use App\Controller\TestFixtures\ResetAction;
use App\Controller\TestFixtures\RouterAction;
use App\Controller\TestFixtures\TestFixtureEvent;
use App\Controller\TestFixtures\TimelineAction;
use App\Controller\TestFixtures\TranslatorAction;
use App\Controller\TestFixtures\ValidatorAction;
use App\Controller\TestFixtures\ViewAction;
use App\Controller\Web\ApiPlaygroundPage;
use App\Controller\Web\ContactPage;
use App\Controller\Web\ErrorDemoPage;
use App\Controller\Web\HomePage;
use App\Controller\Web\LogDemoPage;
use App\Controller\Web\OpenApiPage;
use App\Controller\Web\UsersPage;
use App\Controller\Web\VarDumperPage;
use AppDevPanel\Adapter\Spiral\Bootloader\AppDevPanelBootloader;
use AppDevPanel\Adapter\Spiral\Middleware\AdpApiMiddleware;
use AppDevPanel\Adapter\Spiral\Middleware\DebugMiddleware;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Spiral\Core\Container;

/**
 * Minimal application kernel for the ADP Spiral playground.
 *
 * Boots a small Spiral container (no full `AbstractKernel` / RoadRunner setup needed
 * for the playground — we serve via PHP's built-in web server), registers the ADP
 * bootloader, and builds a PSR-15 middleware pipeline that:
 *
 *   1. `AdpApiMiddleware` — hands `/debug/*` and `/inspect/api/*` to ADP.
 *   2. `DebugMiddleware` — runs the Debugger lifecycle around the app handler.
 *   3. `PathRouter` — simple switch-based dispatcher to fixture endpoints.
 */
final class Kernel
{
    private Container $container;

    public function __construct()
    {
        $this->container = new Container();
        $this->registerAppBindings();
        $this->registerAdpBootloader();
    }

    public function run(): void
    {
        $psr17 = $this->container->get(Psr17Factory::class);
        $creator = new ServerRequestCreator($psr17, $psr17, $psr17, $psr17);
        $request = $creator->fromGlobals();

        $response = $this->buildPipeline()->handle($request);

        $this->emit($response);
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    private function registerAppBindings(): void
    {
        // Shared PSR-17 factory — nyholm covers every factory interface.
        $factory = new Psr17Factory();
        $this->container->bindSingleton(Psr17Factory::class, $factory);

        // Event dispatcher — simple in-memory dispatcher driven by a listener provider
        // so fixture endpoints can emit events visible to the ADP EventCollector.
        $listenerProvider = new class implements ListenerProviderInterface {
            /** @var list<callable> */
            private array $listeners = [];

            public function getListenersForEvent(object $event): iterable
            {
                return $this->listeners;
            }

            public function addListener(callable $listener): void
            {
                $this->listeners[] = $listener;
            }
        };
        $listenerProvider->addListener(static function (TestFixtureEvent $event): void {
            // Mutate event to prove the listener ran.
            $event->handled = true;
        });
        $this->container->bindSingleton(ListenerProviderInterface::class, $listenerProvider);
        $this->container->bindSingleton(EventDispatcherInterface::class, new class($listenerProvider) implements
            EventDispatcherInterface {
            public function __construct(
                private readonly ListenerProviderInterface $provider,
            ) {}

            public function dispatch(object $event): object
            {
                foreach ($this->provider->getListenersForEvent($event) as $listener) {
                    $listener($event);
                }

                return $event;
            }
        });

        // PSR-3 logger — Monolog writing to the playground's var/log/app.log
        $logFile = dirname(__DIR__, 2) . '/var/log/app.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0o777, true);
        }
        $monolog = new MonologLogger('playground');
        $monolog->pushHandler(new StreamHandler($logFile, Level::Debug));
        $this->container->bindSingleton(LoggerInterface::class, $monolog);

        // PSR-18 HTTP client — a tiny built-in client that loops back to the playground
        // itself. Avoids a hard dependency on Guzzle while still exercising the proxy.
        $this->container->bindSingleton(ClientInterface::class, new LoopbackHttpClient());

        // PDO is bound in registerAdpBootloader() once the DatabaseCollector singleton
        // exists in the container — see the closure there.

        // Shared HTML layout helper for the demo pages
        $this->container->bindSingleton(Layout::class, new Layout());

        // Demo web pages — human-facing navigation
        $this->container->bindSingleton(HomePage::class, HomePage::class);
        $this->container->bindSingleton(UsersPage::class, UsersPage::class);
        $this->container->bindSingleton(ContactPage::class, ContactPage::class);
        $this->container->bindSingleton(ApiPlaygroundPage::class, ApiPlaygroundPage::class);
        $this->container->bindSingleton(ErrorDemoPage::class, ErrorDemoPage::class);
        $this->container->bindSingleton(LogDemoPage::class, LogDemoPage::class);
        $this->container->bindSingleton(VarDumperPage::class, VarDumperPage::class);
        $this->container->bindSingleton(OpenApiPage::class, OpenApiPage::class);

        // Test fixture endpoints — consumed by FixtureRunner / PHPUnit E2E
        $this->container->bindSingleton(ResetAction::class, ResetAction::class);
        $this->container->bindSingleton(LogsAction::class, LogsAction::class);
        $this->container->bindSingleton(LogsContextAction::class, LogsContextAction::class);
        $this->container->bindSingleton(LogsHeavyAction::class, LogsHeavyAction::class);
        $this->container->bindSingleton(EventsAction::class, EventsAction::class);
        $this->container->bindSingleton(DumpAction::class, DumpAction::class);
        $this->container->bindSingleton(TimelineAction::class, TimelineAction::class);
        $this->container->bindSingleton(RequestInfoAction::class, RequestInfoAction::class);
        $this->container->bindSingleton(ExceptionAction::class, ExceptionAction::class);
        $this->container->bindSingleton(ExceptionChainedAction::class, ExceptionChainedAction::class);
        $this->container->bindSingleton(MultiAction::class, MultiAction::class);
        $this->container->bindSingleton(HttpClientAction::class, HttpClientAction::class);
        $this->container->bindSingleton(FilesystemAction::class, FilesystemAction::class);
        $this->container->bindSingleton(RouterAction::class, RouterAction::class);
        $this->container->bindSingleton(ValidatorAction::class, ValidatorAction::class);
        $this->container->bindSingleton(CacheAction::class, CacheAction::class);
        $this->container->bindSingleton(CacheHeavyAction::class, CacheHeavyAction::class);
        $this->container->bindSingleton(DatabaseAction::class, DatabaseAction::class);
        $this->container->bindSingleton(TranslatorAction::class, TranslatorAction::class);
        $this->container->bindSingleton(ViewAction::class, ViewAction::class);
        $this->container->bindSingleton(MailerAction::class, MailerAction::class);
        $this->container->bindSingleton(QueueAction::class, QueueAction::class);
    }

    private function registerAdpBootloader(): void
    {
        $bootloader = new AppDevPanelBootloader();

        foreach ($bootloader->defineSingletons() as $abstract => $concrete) {
            $this->container->bindSingleton($abstract, $concrete);
        }

        $bootloader->boot($this->container);

        // Wire the TracingPdo to the DatabaseCollector lazily — both are now
        // registered, so the next time PDO is resolved it'll forward each query
        // to the collector. Done after `boot()` because the bootloader registers
        // the collector singleton in `defineSingletons()`.
        $this->container->bindSingleton(\App\Application\TracingPdo::class, function () {
            $pdo = new \App\Application\TracingPdo('sqlite::memory:');
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->setCollector($this->container->get(\AppDevPanel\Kernel\Collector\DatabaseCollector::class));
            return $pdo;
        });
    }

    private function buildPipeline(): RequestHandlerInterface
    {
        $router = new PathRouter(
            container: $this->container,
            responseFactory: $this->container->get(ResponseFactoryInterface::class),
            streamFactory: $this->container->get(StreamFactoryInterface::class),
            routes: [
                // Demo web pages
                '/' => HomePage::class,
                '/users' => UsersPage::class,
                '/contact' => ContactPage::class,
                '/api-playground' => ApiPlaygroundPage::class,
                '/error' => ErrorDemoPage::class,
                '/log-demo' => LogDemoPage::class,
                '/var-dumper' => VarDumperPage::class,
                '/api/openapi.json' => OpenApiPage::class,

                // Fixture endpoints (machine-consumed by FixtureRunner / PHPUnit E2E)
                '/test/fixtures/reset' => ResetAction::class,
                '/test/fixtures/logs' => LogsAction::class,
                '/test/fixtures/logs-context' => LogsContextAction::class,
                '/test/fixtures/logs-heavy' => LogsHeavyAction::class,
                '/test/fixtures/events' => EventsAction::class,
                '/test/fixtures/dump' => DumpAction::class,
                '/test/fixtures/timeline' => TimelineAction::class,
                '/test/fixtures/request-info' => RequestInfoAction::class,
                '/test/fixtures/exception' => ExceptionAction::class,
                '/test/fixtures/exception-chained' => ExceptionChainedAction::class,
                '/test/fixtures/multi' => MultiAction::class,
                '/test/fixtures/http-client' => HttpClientAction::class,
                '/test/fixtures/filesystem' => FilesystemAction::class,
                '/test/fixtures/router' => RouterAction::class,
                '/test/fixtures/validator' => ValidatorAction::class,
                '/test/fixtures/cache' => CacheAction::class,
                '/test/fixtures/cache-heavy' => CacheHeavyAction::class,
                '/test/fixtures/database' => DatabaseAction::class,
                '/test/fixtures/translator' => TranslatorAction::class,
                '/test/fixtures/view' => ViewAction::class,
                '/test/fixtures/mailer' => MailerAction::class,
                '/test/fixtures/queue' => QueueAction::class,
            ],
            fallback: NotFoundAction::class,
        );

        /** @var list<MiddlewareInterface> $middlewares */
        $middlewares = [
            $this->container->get(AdpApiMiddleware::class),
            $this->container->get(DebugMiddleware::class),
        ];

        return new MiddlewarePipeline($middlewares, $router);
    }

    private function emit(ResponseInterface $response): void
    {
        if (!headers_sent()) {
            header(sprintf(
                'HTTP/%s %d %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase(),
            ));
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        while (!$body->eof()) {
            echo $body->read(8192);
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }
    }
}
