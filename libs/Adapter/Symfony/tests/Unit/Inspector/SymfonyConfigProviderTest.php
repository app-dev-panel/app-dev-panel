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

        $this->assertArrayHasKey('kernel.request', $result);
        $this->assertCount(1, $result['kernel.request']);
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

        $this->assertArrayHasKey('app.event', $result);
        $this->assertStringContainsString('::onEvent', $result['app.event'][0]);
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

        $this->assertArrayHasKey('app.event', $result);
        $this->assertStringContainsString('::__invoke', $result['app.event'][0]);
    }

    public function testGetEventsWithClosureListener(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('app.event', \Closure::fromCallable(static function (): void {}));

        $container = new Container();
        $container->set('event_dispatcher', $dispatcher);

        $provider = new SymfonyConfigProvider($container);
        $result = $provider->get('events');

        $this->assertArrayHasKey('app.event', $result);
        $this->assertNotEmpty($result['app.event'][0]);
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

        $keys = array_keys($result);
        $this->assertSame('a.event', $keys[0]);
        $this->assertSame('z.event', $keys[1]);
    }
}
