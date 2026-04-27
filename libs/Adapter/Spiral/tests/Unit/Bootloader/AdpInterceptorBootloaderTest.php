<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Bootloader;

use AppDevPanel\Adapter\Spiral\Bootloader\AdpInterceptorBootloader;
use AppDevPanel\Adapter\Spiral\Bootloader\AppDevPanelBootloader;
use AppDevPanel\Adapter\Spiral\Interceptor\DebugConsoleInterceptor;
use AppDevPanel\Adapter\Spiral\Interceptor\DebugQueueInterceptor;
use AppDevPanel\Adapter\Spiral\Interceptor\DebugRouteInterceptor;
use AppDevPanel\Adapter\Spiral\Tests\Unit\Interceptor\InterceptorStubsBootstrap;
use PHPUnit\Framework\TestCase;
use Spiral\Console\Bootloader\ConsoleBootloader;
use Spiral\Core\Container;
use Spiral\Queue\QueueRegistry;

final class AdpInterceptorBootloaderTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        InterceptorStubsBootstrap::install();
    }

    public function testDeclaresThreeInterceptorSingletons(): void
    {
        $singletons = new AdpInterceptorBootloader()->defineSingletons();

        self::assertArrayHasKey(DebugRouteInterceptor::class, $singletons);
        self::assertArrayHasKey(DebugConsoleInterceptor::class, $singletons);
        self::assertArrayHasKey(DebugQueueInterceptor::class, $singletons);
        self::assertSame(DebugRouteInterceptor::class, $singletons[DebugRouteInterceptor::class]);
        self::assertSame(DebugConsoleInterceptor::class, $singletons[DebugConsoleInterceptor::class]);
        self::assertSame(DebugQueueInterceptor::class, $singletons[DebugQueueInterceptor::class]);
    }

    public function testDependsOnAppDevPanelBootloader(): void
    {
        $deps = new AdpInterceptorBootloader()->defineDependencies();
        self::assertSame([AppDevPanelBootloader::class], $deps);
    }

    public function testRegistersConsoleAndQueueInterceptorsWhenHostsAreBound(): void
    {
        $container = new Container();
        $consoleBootloader = new ConsoleBootloader();
        $queueRegistry = new QueueRegistry();
        $container->bindSingleton(ConsoleBootloader::class, $consoleBootloader);
        $container->bindSingleton(QueueRegistry::class, $queueRegistry);

        new AdpInterceptorBootloader()->boot($container);

        self::assertSame([DebugConsoleInterceptor::class], $consoleBootloader->registeredInterceptors);
        self::assertSame([DebugQueueInterceptor::class], $queueRegistry->registeredConsumeInterceptors);
    }

    public function testIsNoOpWhenOptionalHostsAreMissing(): void
    {
        $container = new Container();

        // Neither ConsoleBootloader nor QueueRegistry bound — boot must not throw.
        new AdpInterceptorBootloader()->boot($container);

        self::assertFalse($container->has(ConsoleBootloader::class));
        self::assertFalse($container->has(QueueRegistry::class));
    }

    public function testRegistersOnlyConsoleWhenQueueRegistryAbsent(): void
    {
        $container = new Container();
        $consoleBootloader = new ConsoleBootloader();
        $container->bindSingleton(ConsoleBootloader::class, $consoleBootloader);

        new AdpInterceptorBootloader()->boot($container);

        self::assertSame([DebugConsoleInterceptor::class], $consoleBootloader->registeredInterceptors);
        self::assertFalse($container->has(QueueRegistry::class));
    }
}
