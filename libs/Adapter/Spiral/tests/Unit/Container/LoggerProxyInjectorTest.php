<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Container;

use AppDevPanel\Adapter\Spiral\Container\LoggerProxyInjector;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\LoggerInterfaceProxy;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Spiral\Core\Container;

final class LoggerProxyInjectorTest extends TestCase
{
    public function testProxyDecoratesUnderlyingService(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new LogCollector(new TimelineCollector());

        $fake = new class extends AbstractLogger {
            /** @var list<array{0: mixed, 1: string, 2: array}> */
            public array $records = [];

            public function log(mixed $level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = [$level, (string) $message, $context];
            }
        };

        $container->bindSingleton(LoggerInterface::class, $fake);

        $injector = new LoggerProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(LoggerProxyInjector::class, $injector);

        $binder->bindInjector(LoggerInterface::class, LoggerProxyInjector::class);

        $resolved = $container->get(LoggerInterface::class);

        self::assertInstanceOf(LoggerInterfaceProxy::class, $resolved);
        $reflection = new ReflectionProperty(LoggerInterfaceProxy::class, 'decorated');
        self::assertSame($fake, $reflection->getValue($resolved));
    }

    public function testFallsBackToDefaultWhenNothingBound(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new LogCollector(new TimelineCollector());

        $injector = new LoggerProxyInjector($container, $binder, $collector);
        $container->bindSingleton(LoggerProxyInjector::class, $injector);

        $binder->bindInjector(LoggerInterface::class, LoggerProxyInjector::class);

        $resolved = $container->get(LoggerInterface::class);

        self::assertInstanceOf(LoggerInterfaceProxy::class, $resolved);
        $reflection = new ReflectionProperty(LoggerInterfaceProxy::class, 'decorated');
        self::assertInstanceOf(NullLogger::class, $reflection->getValue($resolved));
    }

    public function testCollectorReceivesIntercept(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new LogCollector(new TimelineCollector());
        $collector->startup();

        $fake = new NullLogger();

        $injector = new LoggerProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(LoggerProxyInjector::class, $injector);

        $binder->bindInjector(LoggerInterface::class, LoggerProxyInjector::class);

        /** @var LoggerInterface $logger */
        $logger = $container->get(LoggerInterface::class);
        $logger->info('hello world', ['user' => 'tester']);

        $entries = $collector->getCollected();
        self::assertCount(1, $entries);
        self::assertSame('info', $entries[0]['level']);
        self::assertSame('hello world', $entries[0]['message']);
        self::assertSame(['user' => 'tester'], $entries[0]['context']);
    }
}
