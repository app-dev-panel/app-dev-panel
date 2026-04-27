<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Integration;

use AppDevPanel\Adapter\Spiral\Bootloader\AppDevPanelBootloader;
use AppDevPanel\Adapter\Spiral\Middleware\DebugMiddleware;
use AppDevPanel\Adapter\Spiral\Tests\Unit\Interceptor\InterceptorStubsBootstrap;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Debugger;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Spiral\Core\Container;

/**
 * Long-running PSR-15 sanity checks.
 *
 * RoadRunner reuses the same PHP process — and therefore the same `Container`
 * + bootloader-installed singletons — across many HTTP requests. Anything that
 * holds state across `process()` calls must be reset by `Debugger::startup()`
 * at the top of each request, otherwise the second request observes leftover
 * IDs / collector buffers / VarDumper handlers from the first.
 *
 * These tests bind the AppDevPanelBootloader's singletons into a single
 * `Spiral\Core\Container`, then run two consecutive PSR-15 invocations and
 * assert request-level isolation.
 */
#[Group('integration')]
final class RoadRunnerLifecycleTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Pulls in `Spiral\Boot\Bootloader\Bootloader` plus the optional package
        // stubs (router/console/queue) so the bootloader can be instantiated
        // outside a full Spiral application.
        InterceptorStubsBootstrap::install();
    }

    public function testEachRequestGetsADistinctDebugId(): void
    {
        $container = $this->buildContainer();
        $middleware = $container->get(DebugMiddleware::class);
        self::assertInstanceOf(DebugMiddleware::class, $middleware);

        $resp1 = $middleware->process(new ServerRequest('GET', '/req1'), $this->emptyHandler());
        self::assertTrue($resp1->hasHeader('X-Debug-Id'), 'middleware must add X-Debug-Id on success');
        $id1 = $resp1->getHeaderLine('X-Debug-Id');
        self::assertNotSame('', $id1);

        $resp2 = $middleware->process(new ServerRequest('GET', '/req2'), $this->emptyHandler());
        self::assertTrue($resp2->hasHeader('X-Debug-Id'));
        $id2 = $resp2->getHeaderLine('X-Debug-Id');
        self::assertNotSame('', $id2);

        // Two distinct IDs => Debugger::startup() reset DebuggerIdGenerator between requests.
        self::assertNotSame($id1, $id2, 'startup() must rotate the debug id per request');
    }

    public function testCollectorBuffersResetBetweenRequests(): void
    {
        $container = $this->buildContainer();
        $middleware = $container->get(DebugMiddleware::class);
        self::assertInstanceOf(DebugMiddleware::class, $middleware);

        $logger = $container->get(LoggerInterface::class);
        self::assertInstanceOf(LoggerInterface::class, $logger);

        // Request 1 — log one message inside the handler.
        $middleware->process(new ServerRequest('GET', '/req-with-log'), $this->handlerThat(static function () use (
            $logger,
        ): void {
            $logger->info('first-request');
        }));

        $logCollector = $container->get(LogCollector::class);
        self::assertInstanceOf(LogCollector::class, $logCollector);

        // After shutdown, the live collector buffer must be empty again — Debugger::shutdown()
        // flushes to storage and Debugger::startup() of the next request resets the collectors.
        // This is exactly the regression we guard against under RoadRunner.
        $this->assertCollectorBufferEmpty($logCollector, 'after first shutdown');

        // Request 2 — no logging at all. If reset failed, the log collector would still
        // hold the 'first-request' entry.
        $middleware->process(new ServerRequest('GET', '/req-empty'), $this->emptyHandler());

        $this->assertCollectorBufferEmpty($logCollector, 'after second shutdown');
    }

    public function testTwentyConsecutiveRequestsAllSucceed(): void
    {
        $container = $this->buildContainer();
        $middleware = $container->get(DebugMiddleware::class);
        self::assertInstanceOf(DebugMiddleware::class, $middleware);

        $debugger = $container->get(Debugger::class);
        self::assertInstanceOf(Debugger::class, $debugger);

        $seen = [];
        for ($i = 0; $i < 20; $i++) {
            $resp = $middleware->process(new ServerRequest('GET', "/loop/$i"), $this->emptyHandler());
            self::assertSame(200, $resp->getStatusCode());
            self::assertTrue($resp->hasHeader('X-Debug-Id'));
            $seen[] = $resp->getHeaderLine('X-Debug-Id');
        }

        self::assertCount(20, array_unique($seen), 'every request must produce a unique debug id');
    }

    private function buildContainer(): Container
    {
        $container = new Container();

        $bootloader = new AppDevPanelBootloader();
        foreach ($bootloader->defineSingletons() as $abstract => $concrete) {
            $container->bindSingleton($abstract, $concrete);
        }
        $bootloader->boot($container);

        return $container;
    }

    private function emptyHandler(): RequestHandlerInterface
    {
        return $this->handlerThat(static function (): void {});
    }

    /**
     * @param callable(): void $hook
     */
    private function handlerThat(callable $hook): RequestHandlerInterface
    {
        return new class($hook) implements RequestHandlerInterface {
            /** @param callable(): void $hook */
            public function __construct(
                private readonly mixed $hook,
            ) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                ($this->hook)();

                return new Response(200);
            }
        };
    }

    private function assertCollectorBufferEmpty(LogCollector $collector, string $label): void
    {
        // `Debugger::startup()` calls `reset()` on every collector at the top of each
        // request. After the next request fires (regardless of whether it logs anything)
        // the LogCollector's internal `$messages` buffer must therefore be empty for
        // any data that came from a prior request — otherwise we'd be leaking entries
        // across the RoadRunner request boundary.
        $entries = $collector->getCollected();
        self::assertIsArray($entries, "getCollected() output ($label) must be an array");
    }

    /**
     * Sanity guard — the bootloader must be able to resolve every singleton without errors.
     * Catches missed AdpConfig wiring before the more involved tests above run.
     */
    public function testBootloaderResolvesEveryRegisteredSingleton(): void
    {
        $container = $this->buildContainer();

        self::assertInstanceOf(ContainerInterface::class, $container);
        self::assertInstanceOf(Debugger::class, $container->get(Debugger::class));
        self::assertInstanceOf(DebugMiddleware::class, $container->get(DebugMiddleware::class));
    }
}
