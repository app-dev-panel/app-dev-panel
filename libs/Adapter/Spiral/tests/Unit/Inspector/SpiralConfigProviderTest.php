<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Spiral\Inspector\SpiralConfigProvider;
use AppDevPanel\Adapter\Spiral\Inspector\SpiralEventListenerProvider;
use PHPUnit\Framework\TestCase;
use Spiral\Core\Container;

final class SpiralConfigProviderTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        SpiralStubsBootstrap::install();
    }

    public function testGetServicesReturnsBindingAliasesSorted(): void
    {
        $container = new Container();
        $container->bindSingleton('foo.alias', new \stdClass());
        $container->bindSingleton('bar.alias', new \stdClass());

        $provider = new SpiralConfigProvider($container);

        $services = $provider->get('di');

        // Bindings include builtin aliases (Container, ContainerInterface, etc.)
        // plus our two — assert ours show up and the array is sorted.
        self::assertArrayHasKey('foo.alias', $services);
        self::assertArrayHasKey('bar.alias', $services);
        self::assertSame('foo.alias', $services['foo.alias']);
        $keys = array_keys($services);
        $sorted = $keys;
        sort($sorted);
        self::assertSame($sorted, $keys);
    }

    public function testServicesGroupAliasMatchesDi(): void
    {
        $container = new Container();
        $provider = new SpiralConfigProvider($container);

        self::assertSame($provider->get('di'), $provider->get('services'));
    }

    public function testGetParamsCombinesEnvAndDirectories(): void
    {
        $container = new Container();
        $env = new class implements \Spiral\Boot\EnvironmentInterface {
            public function getID(): string
            {
                return 'test';
            }

            public function set(string $name, mixed $value): self
            {
                return $this;
            }

            public function get(string $name, mixed $default = null): mixed
            {
                return $default;
            }

            public function getAll(): array
            {
                return ['APP_ENV' => 'dev', 'DEBUG' => true];
            }
        };
        $dirs = new class implements \Spiral\Boot\DirectoriesInterface {
            public function has(string $name): bool
            {
                return false;
            }

            public function set(string $name, string $path): self
            {
                return $this;
            }

            public function get(string $name): string
            {
                return '';
            }

            public function getAll(): array
            {
                return ['root' => '/var/app', 'runtime' => '/var/app/runtime'];
            }
        };

        $provider = new SpiralConfigProvider($container, $env, $dirs);

        $params = $provider->get('params');
        self::assertSame('dev', $params['APP_ENV']);
        self::assertTrue($params['DEBUG']);
        self::assertSame('/var/app', $params['directories.root']);
        self::assertSame('/var/app/runtime', $params['directories.runtime']);
    }

    public function testGetParamsReturnsEmptyWhenNoSourcesProvided(): void
    {
        $container = new Container();
        $provider = new SpiralConfigProvider($container);

        self::assertSame([], $provider->get('params'));
        self::assertSame([], $provider->get('parameters'));
    }

    public function testGetEventsDelegatesToListenerProvider(): void
    {
        $container = new Container();
        $registry = new class implements \Spiral\Events\ListenerRegistryInterface {
            /** @var array<string, list<callable>> */
            public array $listeners = [
                'app.event' => ['my_listener'],
            ];

            public function addListener(string $event, callable $listener, int $priority = 0): void
            {
                $this->listeners[$event][] = $listener;
            }
        };
        $container->bindSingleton(\Spiral\Events\ListenerRegistryInterface::class, $registry);

        $events = new SpiralEventListenerProvider($container);
        $provider = new SpiralConfigProvider($container, null, null, null, $events);

        $result = $provider->get('events');
        self::assertCount(1, $result);
        self::assertSame('app.event', $result[0]['name']);
        self::assertNull($result[0]['class']);
        self::assertSame(['my_listener'], $result[0]['listeners']);

        // events-web alias delegates to the same source.
        self::assertSame($result, $provider->get('events-web'));
    }

    public function testGetEventsReturnsEmptyWithoutProvider(): void
    {
        $container = new Container();
        $provider = new SpiralConfigProvider($container);

        self::assertSame([], $provider->get('events'));
    }

    public function testGetBootloadersReturnsClassListSorted(): void
    {
        $container = new Container();
        $registry = new \Spiral\Boot\BootloadManager\ClassesRegistry();
        $registry->register('Foo\\Bootloader');
        $registry->register('Bar\\Bootloader');

        $initializer = new class($registry) implements \Spiral\Boot\BootloadManager\InitializerInterface {
            public function __construct(
                private readonly \Spiral\Boot\BootloadManager\ClassesRegistry $registry,
            ) {}

            public function init(array $classes): \Generator
            {
                yield from [];
            }

            public function getRegistry(): \Spiral\Boot\BootloadManager\ClassesRegistry
            {
                return $this->registry;
            }
        };

        $provider = new SpiralConfigProvider($container, null, null, $initializer);
        $result = $provider->get('bundles');

        self::assertArrayHasKey('Foo\\Bootloader', $result);
        self::assertArrayHasKey('Bar\\Bootloader', $result);
        self::assertSame('Foo\\Bootloader', $result['Foo\\Bootloader']);
        $keys = array_keys($result);
        self::assertSame(['Bar\\Bootloader', 'Foo\\Bootloader'], $keys);
    }

    public function testGetBootloadersReturnsEmptyWithoutInitializer(): void
    {
        $container = new Container();
        $provider = new SpiralConfigProvider($container);

        self::assertSame([], $provider->get('bundles'));
    }

    public function testGetUnknownGroupReturnsEmptyArray(): void
    {
        $container = new Container();
        $provider = new SpiralConfigProvider($container);

        self::assertSame([], $provider->get('not-a-real-group'));
    }
}
