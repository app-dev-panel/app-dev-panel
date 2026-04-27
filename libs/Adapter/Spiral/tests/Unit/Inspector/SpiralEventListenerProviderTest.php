<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Spiral\Inspector\SpiralEventListenerProvider;
use PHPUnit\Framework\TestCase;
use Spiral\Core\Container;
use Spiral\Events\ListenerRegistryInterface;

final class SpiralEventListenerProviderTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        SpiralStubsBootstrap::install();
    }

    public function testEmptyWhenRegistryNotBound(): void
    {
        self::assertSame([], new SpiralEventListenerProvider(new Container())->getInspectorPayload());
    }

    public function testReadsListenersByReflection(): void
    {
        $listenerObject = new class {
            public function __invoke(): void {}
        };

        $registry = new class($listenerObject) implements ListenerRegistryInterface {
            /** @var array<string, list<mixed>> */
            private array $listeners;

            public function __construct(object $listenerObject)
            {
                $this->listeners = [
                    'app.event.b' => ['CallbackString', $listenerObject, ['ServiceClass', 'method']],
                    'app.event.a' => [static fn() => null],
                ];
            }

            public function addListener(string $event, callable $listener, int $priority = 0): void {}
        };

        $container = new Container();
        $container->bindSingleton(ListenerRegistryInterface::class, $registry);

        $payload = new SpiralEventListenerProvider($container)->getInspectorPayload();

        self::assertCount(2, $payload);
        self::assertSame('app.event.a', $payload[0]['name']);
        self::assertSame('app.event.b', $payload[1]['name']);
        self::assertNull($payload[0]['class']);
        self::assertSame('CallbackString', $payload[1]['listeners'][0]);
        self::assertIsArray($payload[1]['listeners'][1]);
        self::assertSame($listenerObject::class, $payload[1]['listeners'][1]['class']);
        self::assertSame('__invoke', $payload[1]['listeners'][1]['method']);
        self::assertSame(['class' => 'ServiceClass', 'method' => 'method'], $payload[1]['listeners'][2]);
    }

    public function testRegistryWithoutListenersPropertyYieldsEmpty(): void
    {
        $registry = new class implements ListenerRegistryInterface {
            public function addListener(string $event, callable $listener, int $priority = 0): void {}
        };

        $container = new Container();
        $container->bindSingleton(ListenerRegistryInterface::class, $registry);

        self::assertSame([], new SpiralEventListenerProvider($container)->getInspectorPayload());
    }
}
