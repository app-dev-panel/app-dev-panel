<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Laravel\Inspector\LaravelConfigProvider;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use PHPUnit\Framework\TestCase;

final class LaravelConfigProviderTest extends TestCase
{
    public function testGetParametersReturnsConfig(): void
    {
        $configData = ['app' => ['name' => 'TestApp'], 'database' => ['default' => 'sqlite']];
        $configRepo = new Repository($configData);

        $app = $this->createAppMock();
        $app->method('make')->willReturnCallback(static function (string $abstract) use ($configRepo): mixed {
            if ($abstract === 'config') {
                return $configRepo;
            }
            return null;
        });

        $provider = new LaravelConfigProvider($app);
        $result = $provider->get('params');

        $this->assertArrayHasKey('app', $result);
        $this->assertSame(['name' => 'TestApp'], $result['app']);
    }

    public function testParamsAndParametersAreAliases(): void
    {
        $configRepo = new Repository(['key' => 'value']);

        $app = $this->createAppMock();
        $app->method('make')->willReturnCallback(static function (string $abstract) use ($configRepo): mixed {
            if ($abstract === 'config') {
                return $configRepo;
            }
            return null;
        });

        $provider = new LaravelConfigProvider($app);

        $this->assertSame($provider->get('params'), $provider->get('parameters'));
    }

    public function testGetUnknownGroupReturnsEmptyArray(): void
    {
        $app = $this->createAppMock();
        $provider = new LaravelConfigProvider($app);

        $this->assertSame([], $provider->get('nonexistent'));
    }

    public function testGetEventsWithNoDispatcherReturnsEmptyArray(): void
    {
        $app = $this->createAppMock();
        $app->method('bound')->with('events')->willReturn(false);

        $provider = new LaravelConfigProvider($app);

        $this->assertSame([], $provider->get('events'));
    }

    public function testGetServicesWithNoGetBindingsMethodReturnsEmptyArray(): void
    {
        // Application interface doesn't have getBindings - test the method_exists guard
        $app = $this->createAppMock();

        $provider = new LaravelConfigProvider($app);
        $result = $provider->get('di');

        $this->assertSame([], $result);
    }

    public function testGetProvidersWithNoGetLoadedProvidersMethodReturnsEmptyArray(): void
    {
        $app = $this->createAppMock();

        $provider = new LaravelConfigProvider($app);
        $result = $provider->get('providers');

        $this->assertSame([], $result);
    }

    public function testGetEventsWithClosureListenerRendersSourceCode(): void
    {
        $closure = static function (): void {};

        $mockDispatcher = new class($closure) {
            /** @var array<string, list<\Closure>> */
            private array $listeners;

            public function __construct(\Closure $closure)
            {
                $this->listeners = ['app.event' => [$closure]];
            }

            /** @return array<string, list<\Closure>> */
            public function getRawListeners(): array
            {
                return $this->listeners;
            }
        };

        $app = $this->createAppMock();
        $app->method('bound')->with('events')->willReturn(true);
        $app->method('make')->willReturnCallback(static function (string $abstract) use ($mockDispatcher): mixed {
            if ($abstract === 'events') {
                return $mockDispatcher;
            }
            return null;
        });

        $provider = new LaravelConfigProvider($app);
        $result = $provider->get('events');

        $this->assertCount(1, $result);
        $this->assertSame('app.event', $result[0]['name']);
        $this->assertCount(1, $result[0]['listeners']);
        $listener = $result[0]['listeners'][0];
        $this->assertIsArray($listener);
        $this->assertTrue($listener['__closure']);
        $this->assertStringContainsString('static function', $listener['source']);
        $this->assertStringContainsString('void', $listener['source']);
        $this->assertSame(__FILE__, $listener['file']);
        $this->assertIsInt($listener['startLine']);
        $this->assertIsInt($listener['endLine']);
    }

    public function testGetEventsWithClosureBodyRendered(): void
    {
        $closure = static function (object $event): string {
            return $event::class;
        };

        $mockDispatcher = new class($closure) {
            private array $listeners;

            public function __construct(\Closure $closure)
            {
                $this->listeners = ['app.event' => [$closure]];
            }

            public function getRawListeners(): array
            {
                return $this->listeners;
            }
        };

        $app = $this->createAppMock();
        $app->method('bound')->with('events')->willReturn(true);
        $app->method('make')->willReturnCallback(static function (string $abstract) use ($mockDispatcher): mixed {
            if ($abstract === 'events') {
                return $mockDispatcher;
            }
            return null;
        });

        $provider = new LaravelConfigProvider($app);
        $result = $provider->get('events');

        $listener = $result[0]['listeners'][0];
        $this->assertIsArray($listener);
        $this->assertStringContainsString('static function', $listener['source']);
        $this->assertStringContainsString('object $event', $listener['source']);
        $this->assertStringContainsString('return $event::class', $listener['source']);
    }

    public function testGetEventsWithArrowFunctionListener(): void
    {
        $closure = static fn(object $e): string => $e::class;

        $mockDispatcher = new class($closure) {
            private array $listeners;

            public function __construct(\Closure $closure)
            {
                $this->listeners = ['app.event' => [$closure]];
            }

            public function getRawListeners(): array
            {
                return $this->listeners;
            }
        };

        $app = $this->createAppMock();
        $app->method('bound')->with('events')->willReturn(true);
        $app->method('make')->willReturnCallback(static function (string $abstract) use ($mockDispatcher): mixed {
            if ($abstract === 'events') {
                return $mockDispatcher;
            }
            return null;
        });

        $provider = new LaravelConfigProvider($app);
        $result = $provider->get('events');

        $listener = $result[0]['listeners'][0];
        $this->assertIsArray($listener);
        $this->assertStringContainsString('fn', $listener['source']);
        $this->assertStringContainsString('$e', $listener['source']);
    }

    public function testGetEventsWithCallableArrayListener(): void
    {
        $handler = new class {
            public function onEvent(): void {}
        };

        $mockDispatcher = new class($handler) {
            private array $listeners;

            public function __construct(object $handler)
            {
                $this->listeners = ['app.event' => [[$handler, 'onEvent']]];
            }

            public function getRawListeners(): array
            {
                return $this->listeners;
            }
        };

        $app = $this->createAppMock();
        $app->method('bound')->with('events')->willReturn(true);
        $app->method('make')->willReturnCallback(static function (string $abstract) use ($mockDispatcher): mixed {
            if ($abstract === 'events') {
                return $mockDispatcher;
            }
            return null;
        });

        $provider = new LaravelConfigProvider($app);
        $result = $provider->get('events');

        $this->assertStringContainsString('::onEvent', $result[0]['listeners'][0]);
    }

    public function testGetEventsWithInvokableListener(): void
    {
        $handler = new class {
            public function __invoke(): void {}
        };

        $mockDispatcher = new class($handler) {
            private array $listeners;

            public function __construct(object $handler)
            {
                $this->listeners = ['app.event' => [$handler]];
            }

            public function getRawListeners(): array
            {
                return $this->listeners;
            }
        };

        $app = $this->createAppMock();
        $app->method('bound')->with('events')->willReturn(true);
        $app->method('make')->willReturnCallback(static function (string $abstract) use ($mockDispatcher): mixed {
            if ($abstract === 'events') {
                return $mockDispatcher;
            }
            return null;
        });

        $provider = new LaravelConfigProvider($app);
        $result = $provider->get('events');

        $this->assertStringContainsString('::__invoke', $result[0]['listeners'][0]);
    }

    public function testGetEventsWithStringListener(): void
    {
        $mockDispatcher = new class {
            public function getRawListeners(): array
            {
                return ['app.event' => ['App\\Listeners\\MyListener']];
            }
        };

        $app = $this->createAppMock();
        $app->method('bound')->with('events')->willReturn(true);
        $app->method('make')->willReturnCallback(static function (string $abstract) use ($mockDispatcher): mixed {
            if ($abstract === 'events') {
                return $mockDispatcher;
            }
            return null;
        });

        $provider = new LaravelConfigProvider($app);
        $result = $provider->get('events');

        $this->assertSame('App\\Listeners\\MyListener', $result[0]['listeners'][0]);
    }

    public function testGetDiIsAliasForServices(): void
    {
        $app = $this->createAppMock();
        $provider = new LaravelConfigProvider($app);

        $this->assertSame($provider->get('di'), $provider->get('services'));
    }

    public function testGetBundlesIsAliasForProviders(): void
    {
        $app = $this->createAppMock();
        $provider = new LaravelConfigProvider($app);

        $this->assertSame($provider->get('bundles'), $provider->get('providers'));
    }

    public function testGetEventsWebIsAliasForEvents(): void
    {
        $app = $this->createAppMock();
        $app->method('bound')->with('events')->willReturn(false);

        $provider = new LaravelConfigProvider($app);

        $this->assertSame($provider->get('events'), $provider->get('events-web'));
    }

    public function testGetEventsWithDispatcherLackingGetRawListeners(): void
    {
        $dispatcherWithoutMethod = new class {
            // No getRawListeners method
        };

        $app = $this->createAppMock();
        $app->method('bound')->with('events')->willReturn(true);
        $app->method('make')->willReturnCallback(static function (string $abstract) use (
            $dispatcherWithoutMethod,
        ): mixed {
            if ($abstract === 'events') {
                return $dispatcherWithoutMethod;
            }
            return null;
        });

        $provider = new LaravelConfigProvider($app);

        $this->assertSame([], $provider->get('events'));
    }

    public function testGetEventsWithClassNameEvent(): void
    {
        $mockDispatcher = new class {
            public function getRawListeners(): array
            {
                return [\stdClass::class => ['SomeListener']];
            }
        };

        $app = $this->createAppMock();
        $app->method('bound')->with('events')->willReturn(true);
        $app->method('make')->willReturnCallback(static function (string $abstract) use ($mockDispatcher): mixed {
            if ($abstract === 'events') {
                return $mockDispatcher;
            }
            return null;
        });

        $provider = new LaravelConfigProvider($app);
        $result = $provider->get('events');

        $this->assertCount(1, $result);
        $this->assertSame(\stdClass::class, $result[0]['name']);
        $this->assertSame(\stdClass::class, $result[0]['class']);
    }

    public function testGetEventsWithNonClassNameEvent(): void
    {
        $mockDispatcher = new class {
            public function getRawListeners(): array
            {
                return ['custom.event' => ['SomeListener']];
            }
        };

        $app = $this->createAppMock();
        $app->method('bound')->with('events')->willReturn(true);
        $app->method('make')->willReturnCallback(static function (string $abstract) use ($mockDispatcher): mixed {
            if ($abstract === 'events') {
                return $mockDispatcher;
            }
            return null;
        });

        $provider = new LaravelConfigProvider($app);
        $result = $provider->get('events');

        $this->assertCount(1, $result);
        $this->assertSame('custom.event', $result[0]['name']);
        $this->assertNull($result[0]['class']);
    }

    public function testGetEventsWithStringArrayListener(): void
    {
        $mockDispatcher = new class {
            public function getRawListeners(): array
            {
                return ['app.event' => [['App\\Listeners\\MyListener', 'handle']]];
            }
        };

        $app = $this->createAppMock();
        $app->method('bound')->with('events')->willReturn(true);
        $app->method('make')->willReturnCallback(static function (string $abstract) use ($mockDispatcher): mixed {
            if ($abstract === 'events') {
                return $mockDispatcher;
            }
            return null;
        });

        $provider = new LaravelConfigProvider($app);
        $result = $provider->get('events');

        $this->assertSame('App\\Listeners\\MyListener::handle', $result[0]['listeners'][0]);
    }

    public function testGetEventsWithUnknownListenerType(): void
    {
        $mockDispatcher = new class {
            public function getRawListeners(): array
            {
                return ['app.event' => [42]]; // int is not a known listener type
            }
        };

        $app = $this->createAppMock();
        $app->method('bound')->with('events')->willReturn(true);
        $app->method('make')->willReturnCallback(static function (string $abstract) use ($mockDispatcher): mixed {
            if ($abstract === 'events') {
                return $mockDispatcher;
            }
            return null;
        });

        $provider = new LaravelConfigProvider($app);
        $result = $provider->get('events');

        $this->assertSame('int', $result[0]['listeners'][0]);
    }

    public function testGetEventsMultipleEventsSorted(): void
    {
        $mockDispatcher = new class {
            public function getRawListeners(): array
            {
                return [
                    'z.event' => ['ZListener'],
                    'a.event' => ['AListener'],
                    'm.event' => ['MListener'],
                ];
            }
        };

        $app = $this->createAppMock();
        $app->method('bound')->with('events')->willReturn(true);
        $app->method('make')->willReturnCallback(static function (string $abstract) use ($mockDispatcher): mixed {
            if ($abstract === 'events') {
                return $mockDispatcher;
            }
            return null;
        });

        $provider = new LaravelConfigProvider($app);
        $result = $provider->get('events');

        $this->assertCount(3, $result);
        $this->assertSame('a.event', $result[0]['name']);
        $this->assertSame('m.event', $result[1]['name']);
        $this->assertSame('z.event', $result[2]['name']);
    }

    /**
     * @return Application&\PHPUnit\Framework\MockObject\MockObject
     */
    private function createAppMock(): Application
    {
        return $this->createMock(Application::class);
    }
}
