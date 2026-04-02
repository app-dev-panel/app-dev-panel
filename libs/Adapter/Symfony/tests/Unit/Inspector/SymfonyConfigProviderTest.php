<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Symfony\Inspector\SymfonyConfigProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class SymfonyConfigProviderTest extends TestCase
{
    public function testGetParametersGroup(): void
    {
        $container = new Container();
        $params = ['kernel.debug' => true, 'kernel.environment' => 'dev'];
        $provider = new SymfonyConfigProvider($container, $params);

        $result = $provider->get('params');

        $this->assertSame($params, $result);
    }

    public function testGetParametersGroupAlias(): void
    {
        $params = ['foo' => 'bar'];
        $provider = new SymfonyConfigProvider(new Container(), $params);

        $this->assertSame($params, $provider->get('parameters'));
    }

    public function testGetBundlesGroup(): void
    {
        $bundleConfig = ['framework' => ['secret' => '***']];
        $provider = new SymfonyConfigProvider(new Container(), [], $bundleConfig);

        $this->assertSame($bundleConfig, $provider->get('bundles'));
    }

    public function testGetServicesGroupWithServiceIds(): void
    {
        $container = new Container();
        $container->set('foo', new \stdClass());
        $provider = new SymfonyConfigProvider($container);

        $result = $provider->get('di');

        $this->assertIsArray($result);
    }

    public function testGetServicesGroupAlias(): void
    {
        $provider = new SymfonyConfigProvider(new Container());

        $diResult = $provider->get('di');
        $servicesResult = $provider->get('services');

        $this->assertSame($diResult, $servicesResult);
    }

    public function testGetEventsGroupWithDispatcher(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('kernel.request', static function (): void {});

        $container = new Container();
        $container->set('event_dispatcher', $dispatcher);

        $provider = new SymfonyConfigProvider($container);
        $result = $provider->get('events');

        $this->assertCount(1, $result);
        $this->assertSame('kernel.request', $result[0]['name']);
        $this->assertNull($result[0]['class']);
        $this->assertCount(1, $result[0]['listeners']);
    }

    public function testGetEventsGroupWithoutDispatcher(): void
    {
        $provider = new SymfonyConfigProvider(new Container());

        $this->assertSame([], $provider->get('events'));
    }

    public function testGetEventsWithCallableListener(): void
    {
        $dispatcher = new EventDispatcher();
        $listener = new class {
            public function onEvent(): void {}
        };
        $dispatcher->addListener('app.event', [$listener, 'onEvent']);

        $container = new Container();
        $container->set('event_dispatcher', $dispatcher);

        $provider = new SymfonyConfigProvider($container);
        $result = $provider->get('events');

        $this->assertCount(1, $result);
        $this->assertSame('app.event', $result[0]['name']);
        $this->assertStringContainsString('::onEvent', $result[0]['listeners'][0]);
    }

    public function testGetUnknownGroupReturnsEmpty(): void
    {
        $provider = new SymfonyConfigProvider(new Container());

        $this->assertSame([], $provider->get('nonexistent'));
    }

    public function testGetEventsWebReturnsSameAsEvents(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('kernel.request', static function (): void {});

        $container = new Container();
        $container->set('event_dispatcher', $dispatcher);

        $provider = new SymfonyConfigProvider($container);

        $this->assertSame($provider->get('events'), $provider->get('events-web'));
    }

    public function testGetEventsWithInvokableListener(): void
    {
        $dispatcher = new EventDispatcher();
        $listener = new class {
            public function __invoke(): void {}
        };
        $dispatcher->addListener('app.event', $listener);

        $container = new Container();
        $container->set('event_dispatcher', $dispatcher);

        $provider = new SymfonyConfigProvider($container);
        $result = $provider->get('events');

        $this->assertCount(1, $result);
        $this->assertSame('app.event', $result[0]['name']);
        $this->assertStringContainsString('::__invoke', $result[0]['listeners'][0]);
    }

    public function testGetEventsWithClosureListener(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('app.event', \Closure::fromCallable(static function (): void {}));

        $container = new Container();
        $container->set('event_dispatcher', $dispatcher);

        $provider = new SymfonyConfigProvider($container);
        $result = $provider->get('events');

        $this->assertCount(1, $result);
        $this->assertSame('app.event', $result[0]['name']);
        $listener = $result[0]['listeners'][0];
        $this->assertIsArray($listener);
        $this->assertTrue($listener['__closure']);
        $this->assertStringContainsString('static function', $listener['source']);
        $this->assertStringContainsString('void', $listener['source']);
        $this->assertSame(__FILE__, $listener['file']);
        $this->assertIsInt($listener['startLine']);
        $this->assertIsInt($listener['endLine']);
    }

    public function testGetEventsWithClosureListenerRendersBody(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('app.event', static function (object $event): string {
            return $event::class;
        });

        $container = new Container();
        $container->set('event_dispatcher', $dispatcher);

        $provider = new SymfonyConfigProvider($container);
        $result = $provider->get('events');

        $listener = $result[0]['listeners'][0];
        $this->assertIsArray($listener);
        $this->assertStringContainsString('static function', $listener['source']);
        $this->assertStringContainsString('object $event', $listener['source']);
        $this->assertStringContainsString('return $event::class', $listener['source']);
    }

    public function testGetEventsWithArrowFunctionListener(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('app.event', static fn(object $e): string => $e::class);

        $container = new Container();
        $container->set('event_dispatcher', $dispatcher);

        $provider = new SymfonyConfigProvider($container);
        $result = $provider->get('events');

        $listener = $result[0]['listeners'][0];
        $this->assertIsArray($listener);
        $this->assertStringContainsString('fn', $listener['source']);
        $this->assertStringContainsString('$e', $listener['source']);
    }

    public function testGetEventsWhenDispatcherIsNotEventDispatcherInterface(): void
    {
        $container = new Container();
        $container->set('event_dispatcher', new \stdClass());

        $provider = new SymfonyConfigProvider($container);
        $result = $provider->get('events');

        $this->assertSame([], $result);
    }

    public function testGetEventsListenersSorted(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('z.event', static function (): void {});
        $dispatcher->addListener('a.event', static function (): void {});

        $container = new Container();
        $container->set('event_dispatcher', $dispatcher);

        $provider = new SymfonyConfigProvider($container);
        $result = $provider->get('events');

        $this->assertCount(2, $result);
        $this->assertSame('a.event', $result[0]['name']);
        $this->assertSame('z.event', $result[1]['name']);
    }

    public function testGetEventsResolvesClassFromAliases(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('console.command', static function (): void {});

        $container = new Container();
        $container->set('event_dispatcher', $dispatcher);

        $provider = new SymfonyConfigProvider($container, [
            'event_dispatcher.event_aliases' => [
                'Symfony\\Component\\Console\\Event\\ConsoleCommandEvent' => 'console.command',
            ],
        ]);
        $result = $provider->get('events');

        $this->assertCount(1, $result);
        $this->assertSame('console.command', $result[0]['name']);
        $this->assertSame('Symfony\\Component\\Console\\Event\\ConsoleCommandEvent', $result[0]['class']);
    }

    public function testGetEventsResolvesClassFromFqcn(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(\stdClass::class, static function (): void {});

        $container = new Container();
        $container->set('event_dispatcher', $dispatcher);

        $provider = new SymfonyConfigProvider($container);
        $result = $provider->get('events');

        $this->assertCount(1, $result);
        $this->assertSame(\stdClass::class, $result[0]['name']);
        $this->assertSame(\stdClass::class, $result[0]['class']);
    }

    public function testBuildReverseAliasMapWithNonArrayAliases(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('some.event', static function (): void {});

        $container = new Container();
        $container->set('event_dispatcher', $dispatcher);

        // Pass non-array event_dispatcher.event_aliases — should not crash
        $provider = new SymfonyConfigProvider($container, [
            'event_dispatcher.event_aliases' => 'not-an-array',
        ]);
        $result = $provider->get('events');

        $this->assertCount(1, $result);
        $this->assertSame('some.event', $result[0]['name']);
        $this->assertNull($result[0]['class']);
    }

    public function testBuildReverseAliasMapSkipsNonStringEntries(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('some.event', static function (): void {});

        $container = new Container();
        $container->set('event_dispatcher', $dispatcher);

        // Pass aliases with non-string keys/values — should be skipped
        $provider = new SymfonyConfigProvider($container, [
            'event_dispatcher.event_aliases' => [
                42 => 'some.event', // non-string key
                'ValidClass' => 123, // non-string value
                'ActualClass' => 'some.event', // valid entry
            ],
        ]);
        $result = $provider->get('events');

        $this->assertCount(1, $result);
        $this->assertSame('some.event', $result[0]['name']);
        // Only the valid 'ActualClass' => 'some.event' entry should work
        $this->assertSame('ActualClass', $result[0]['class']);
    }

    public function testDescribeListenerWithStringListener(): void
    {
        $dispatcher = new EventDispatcher();
        // Symfony EventDispatcher wraps string listeners, but we can test via the provider
        // by using a closure that returns a string — this tests the describeListener path.
        // Actually, we need to test via the internal method.
        // String listeners appear when Symfony lazy-loads subscriber references.
        // Let's test using a plain string callable:
        $dispatcher->addListener('app.event', 'strlen');

        $container = new Container();
        $container->set('event_dispatcher', $dispatcher);

        $provider = new SymfonyConfigProvider($container);
        $result = $provider->get('events');

        $this->assertCount(1, $result);
        $this->assertSame('strlen', $result[0]['listeners'][0]);
    }

    public function testDescribeListenerWithNonInvokableObject(): void
    {
        // Create a dispatcher that has a non-invokable, non-array listener
        // This exercises the get_debug_type fallback path (line 141).
        // We can't easily add a non-callable to Symfony's EventDispatcher,
        // so we test the provider's describeListener indirectly via reflection.
        $container = new Container();
        $provider = new SymfonyConfigProvider($container);

        $reflection = new \ReflectionMethod($provider, 'describeListener');
        $reflection->setAccessible(true);

        // Pass an integer — exercises the get_debug_type fallback
        $result = $reflection->invoke($provider, 42);
        $this->assertSame('int', $result);
    }

    public function testGetServicesGroupWithContainerWithoutGetServiceIds(): void
    {
        // Create a minimal ContainerInterface implementation without getServiceIds()
        $container = new class implements \Symfony\Component\DependencyInjection\ContainerInterface {
            public function set(string $id, ?object $service): void {}

            public function get(string $id, int $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE): ?object
            {
                return null;
            }

            public function has(string $id): bool
            {
                return false;
            }

            public function initialized(string $id): bool
            {
                return false;
            }

            public function getParameter(string $name): \UnitEnum|float|int|bool|array|string|null
            {
                return null;
            }

            public function hasParameter(string $name): bool
            {
                return false;
            }

            public function setParameter(string $name, \UnitEnum|float|int|bool|array|string|null $value): void {}

            // Notably: no getServiceIds() method
        };

        $provider = new SymfonyConfigProvider($container);
        $result = $provider->get('services');

        $this->assertSame([], $result);
    }
}
