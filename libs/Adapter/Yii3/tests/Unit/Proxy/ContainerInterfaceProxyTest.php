<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Proxy;

use AppDevPanel\Adapter\Yii3\Proxy\ContainerInterfaceProxy;
use AppDevPanel\Adapter\Yii3\Proxy\ContainerProxyConfig;
use AppDevPanel\Adapter\Yii3\Proxy\ServiceMethodProxy;
use AppDevPanel\Adapter\Yii3\Proxy\ServiceProxy;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\LoggerInterfaceProxy;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Support\Stub\BrokenProxyImplementation;
use AppDevPanel\Kernel\Tests\Support\Stub\Implementation1;
use AppDevPanel\Kernel\Tests\Support\Stub\Implementation2;
use AppDevPanel\Kernel\Tests\Support\Stub\Interface1;
use AppDevPanel\Kernel\Tests\Support\Stub\Interface2;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use stdClass;
use Throwable;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Files\FileHelper;

final class ContainerInterfaceProxyTest extends TestCase
{
    private string $path = 'tests/container-proxy';

    protected function tearDown(): void
    {
        parent::tearDown();
        FileHelper::removeDirectory($this->path);
    }

    public function testImmutability(): void
    {
        $containerProxy = new ContainerInterfaceProxy(
            new Container(ContainerConfig::create()),
            new ContainerProxyConfig(),
        );

        $this->assertNotSame($containerProxy, $containerProxy->withDecoratedServices([
            LoggerInterface::class => [LoggerInterfaceProxy::class, LogCollector::class],
        ]));
    }

    public function testGetAndHas(): void
    {
        $containerProxy = new ContainerInterfaceProxy($this->createContainer(), $this->createConfig());

        $this->assertTrue($containerProxy->isActive());
        $this->assertTrue($containerProxy->has(LoggerInterface::class));
        $this->assertInstanceOf(LoggerInterfaceProxy::class, $containerProxy->get(LoggerInterface::class));
    }

    public function testGetAndHasWithCallableServices(): void
    {
        $dispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $config = new ContainerProxyConfig(
            true,
            [
                LoggerInterface::class =>
                    static fn(ContainerInterface $container) => $container->get(LoggerInterfaceProxy::class),
                EventDispatcherInterface::class => [
                    EventDispatcherInterfaceProxy::class,
                    EventCollector::class,
                ],
            ],
            $dispatcherMock,
            $this->createServiceCollector(),
            $this->path,
            ContainerInterfaceProxy::LOG_ARGUMENTS,
        );
        $containerProxy = new ContainerInterfaceProxy($this->createContainer(), $config);

        $this->assertTrue($containerProxy->isActive());
        $this->assertTrue($containerProxy->has(LoggerInterface::class));

        $containerProxy->get(LogCollector::class)->startup();
        $containerProxy->get(LoggerInterface::class)->log('test', 'test message');
        $this->assertNotEmpty($containerProxy->get(LogCollector::class)->getCollected());
    }

    public function testGetWithArrayConfigWithStringKeys(): void
    {
        $dispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $serviceCollector = $this->createServiceCollector();
        $serviceCollector->startup();

        $config = new ContainerProxyConfig(
            true,
            [
                LoggerInterface::class => ['logger' => LoggerInterfaceProxy::class],
                EventDispatcherInterface::class => [
                    EventDispatcherInterfaceProxy::class,
                    EventCollector::class,
                ],
            ],
            $dispatcherMock,
            $serviceCollector,
            $this->path,
            ContainerInterfaceProxy::LOG_ARGUMENTS,
        );
        $container = $this->createContainer();
        $containerProxy = new ContainerInterfaceProxy($container, $config);

        $this->assertTrue($containerProxy->isActive());
        $this->assertInstanceOf(LoggerInterface::class, $containerProxy->get(LoggerInterface::class));

        $containerProxy->get(LoggerInterface::class)->log('test', 'test message');
        $this->assertInstanceOf(LoggerInterface::class, $containerProxy->get(LoggerInterface::class));
        $this->assertNotEmpty($config->getCollector()->getCollected());
    }

    public function testGetWithoutConfig(): void
    {
        $dispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $config = new ContainerProxyConfig(
            true,
            [
                LoggerInterface::class => [LoggerInterfaceProxy::class, LogCollector::class],
                EventDispatcherInterface::class,
            ],
            $dispatcherMock,
            $this->createServiceCollector(),
            $this->path,
            ContainerInterfaceProxy::LOG_ARGUMENTS,
        );
        $containerProxy = new ContainerInterfaceProxy($this->createContainer(), $config);

        $this->assertInstanceOf(EventDispatcherInterface::class, $containerProxy->get(EventDispatcherInterface::class));
        $this->assertInstanceOf(
            stdClass::class,
            $containerProxy->get(EventDispatcherInterface::class)->dispatch(new stdClass()),
        );
    }

    public function testGetAndHasWithWrongId(): void
    {
        $containerProxy = new ContainerInterfaceProxy($this->createContainer(), $this->createConfig());

        $this->assertFalse($containerProxy->has(CollectorInterface::class));

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage(sprintf(
            'No definition or class found or resolvable for "%s" while building it.',
            CollectorInterface::class,
        ));
        $containerProxy->get(CollectorInterface::class);
    }

    public function testGetContainerItself(): void
    {
        $containerProxy = new ContainerInterfaceProxy($this->createContainer(), $this->createConfig());

        $this->assertTrue($containerProxy->has(ContainerInterface::class));

        $container = $containerProxy->get(ContainerInterface::class);
        $this->assertNotNull($container);
        $this->assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testGetAndHasWithNotService(): void
    {
        $containerProxy = new ContainerInterfaceProxy($this->createContainer(), $this->createConfig());

        $this->assertTrue($containerProxy->has(ListenerProviderInterface::class));
        $this->assertNotNull($containerProxy->get(ListenerProviderInterface::class));
        $this->assertInstanceOf(
            ListenerProviderInterface::class,
            $containerProxy->get(ListenerProviderInterface::class),
        );
    }

    public function testHasThrowsExceptionButErrorInCollectorIsAbsent(): void
    {
        $container = new CompositeContainer();
        $container->attach(container: new class() implements ContainerInterface {
            public function get($id)
            {
                throw new class() extends Exception implements ContainerExceptionInterface {};
            }

            public function has($id): bool
            {
                throw new class() extends Exception implements ContainerExceptionInterface {};
            }
        });
        $container->attach($container);

        $config = $this->createConfig(ContainerInterfaceProxy::LOG_NOTHING);
        $serviceCollector = $config->getCollector();
        $serviceCollector->startup();
        $containerProxy = new ContainerInterfaceProxy($container, $config);

        $thrown = null;
        try {
            $containerProxy->has('123');
        } catch (Throwable $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown);
        $this->assertNotNull($containerProxy->getCurrentError());

        $data = $serviceCollector->getCollected();
        $this->assertCount(1, $data);
        $this->assertSame(ContainerInterface::class, $data[0]['service']);
        $this->assertSame(CompositeContainer::class, $data[0]['class']);
        $this->assertSame('has', $data[0]['method']);
        $this->assertSame('failed', $data[0]['status']);
        $this->assertNull($data[0]['error']);
    }

    public function testHasThrowsExceptionAndErrorInCollectorIsNotEmpty(): void
    {
        $container = new CompositeContainer();
        $container->attach(new class() implements ContainerInterface {
            public function get($id)
            {
                throw new class() extends Exception implements ContainerExceptionInterface {};
            }

            public function has($id): bool
            {
                throw new class() extends Exception implements ContainerExceptionInterface {};
            }
        });
        $container->attach($container);

        $config = $this->createConfig(ContainerInterfaceProxy::LOG_ERROR);
        $serviceCollector = $config->getCollector();
        $serviceCollector->startup();
        $containerProxy = new ContainerInterfaceProxy($container, $config);

        $thrown = null;
        try {
            $containerProxy->has('123');
        } catch (Throwable $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown);
        $this->assertNotNull($containerProxy->getCurrentError());

        $data = $serviceCollector->getCollected();
        $this->assertCount(1, $data);
        $this->assertSame(ContainerInterface::class, $data[0]['service']);
        $this->assertSame(CompositeContainer::class, $data[0]['class']);
        $this->assertSame('has', $data[0]['method']);
        $this->assertSame('failed', $data[0]['status']);
        $this->assertNotNull($data[0]['error']);
    }

    public function testProxyIsNotNeeded(): void
    {
        $config = $this->createConfig(ContainerInterfaceProxy::LOG_ERROR);
        $config = $config->withDecoratedServices([
            Implementation1::class => Implementation1::class,
        ]);
        $serviceCollector = $config->getCollector();
        $serviceCollector->startup();
        $container = $this->createContainer([
            Implementation1::class => Implementation1::class,
        ]);
        $containerProxy = new ContainerInterfaceProxy($container, $config);

        $implementation = $containerProxy->get(Implementation1::class);
        $this->assertNotNull($implementation);
        $this->assertInstanceOf(Implementation1::class, $implementation);
    }

    public function testBrokenProxyConstructor(): void
    {
        $config = $this->createConfig(ContainerInterfaceProxy::LOG_ERROR);
        $config = $config->withDecoratedServices([
            Interface1::class => [BrokenProxyImplementation::class, stdClass::class],
        ]);
        $serviceCollector = $config->getCollector();
        $serviceCollector->startup();
        $container = $this->createContainer([
            Interface1::class => Implementation1::class,
        ]);
        $containerProxy = new ContainerInterfaceProxy($container, $config);

        $implementation = $containerProxy->get(Interface1::class);
        $this->assertNotNull($implementation);
        $this->assertInstanceOf(Implementation1::class, $implementation);
    }

    public function testMethodCallbackProxy(): void
    {
        $config = $this->createConfig(ContainerInterfaceProxy::LOG_ERROR);
        $config = $config->withDecoratedServices([
            Interface2::class => ['getName' => static fn() => 'from tests'],
        ]);
        $serviceCollector = $config->getCollector();
        $serviceCollector->startup();
        $container = $this->createContainer([
            Interface2::class => Implementation2::class,
        ]);
        $containerProxy = new ContainerInterfaceProxy($container, $config);

        $implementation = $containerProxy->get(Interface2::class);
        $this->assertNotNull($implementation);
        $this->assertInstanceOf(Interface2::class, $implementation);
        $this->assertSame('from tests', $implementation->getName());
    }

    public function testStringKeyServiceProxy(): void
    {
        $config = $this->createConfig(ContainerInterfaceProxy::LOG_ERROR);
        $config = $config->withDecoratedServices([
            'test-interface' => ['getName' => static fn() => 'from tests'],
        ]);
        $serviceCollector = $config->getCollector();
        $serviceCollector->startup();
        $container = $this->createContainer([
            'test-interface' => Implementation2::class,
        ]);
        $containerProxy = new ContainerInterfaceProxy($container, $config);

        $implementation = $containerProxy->get('test-interface');
        $this->assertNotNull($implementation);
        $this->assertInstanceOf(Interface2::class, $implementation);
        $this->assertSame('from tests', $implementation->getName());
    }

    public function testProxyDecoratedCall(): void
    {
        $container = new class() implements ContainerInterface {
            public $var = null;

            public function getProxiedCall(): string
            {
                return 'ok';
            }

            public function setProxiedCall($args): mixed
            {
                return $args;
            }

            public function get($id)
            {
                throw new class() extends Exception implements ContainerExceptionInterface {};
            }

            public function has($id): bool
            {
                throw new class() extends Exception implements ContainerExceptionInterface {};
            }
        };
        $proxy = new ContainerInterfaceProxy($container, new ContainerProxyConfig());

        $this->assertEquals('ok', $proxy->getProxiedCall());
        $this->assertEquals($args = [1, new stdClass(), 'string'], $proxy->setProxiedCall($args));
        $proxy->var = '123';
        $this->assertEquals('123', $proxy->var);
    }

    private function createConfig(int $logLevel = ContainerInterfaceProxy::LOG_ARGUMENTS): ContainerProxyConfig
    {
        return new ContainerProxyConfig(
            true,
            [
                LoggerInterface::class => [LoggerInterfaceProxy::class, LogCollector::class],
                EventDispatcherInterface::class => [
                    EventDispatcherInterfaceProxy::class,
                    EventCollector::class,
                ],
            ],
            $this->createMock(EventDispatcherInterface::class),
            $this->createServiceCollector(),
            $this->path,
            $logLevel,
        );
    }

    private function createContainer(array $definitions = []): Container
    {
        $config = ContainerConfig::create()->withDefinitions([
            EventDispatcherInterface::class => Dispatcher::class,
            ListenerProviderInterface::class => Provider::class,
            LoggerInterface::class => NullLogger::class,
            LogCollector::class => LogCollector::class,
            EventCollector::class => EventCollector::class,
            ...$definitions,
        ]);
        return new Container($config);
    }

    private function createServiceCollector(): ServiceCollector
    {
        return new ServiceCollector(new TimelineCollector());
    }
}
