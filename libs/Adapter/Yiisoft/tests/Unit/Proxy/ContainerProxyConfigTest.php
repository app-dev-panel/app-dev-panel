<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Tests\Unit\Proxy;

use AppDevPanel\Adapter\Yiisoft\Proxy\ContainerProxyConfig;
use AppDevPanel\Adapter\Yiisoft\Proxy\ServiceConfigType;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\LoggerInterfaceProxy;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

final class ContainerProxyConfigTest extends TestCase
{
    public function testImmutability(): void
    {
        $config = new ContainerProxyConfig();

        $dispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $this->assertNotSame($config, $config->activate());
        $this->assertNotSame($config, $config->withCollector($this->createServiceCollector()));
        $this->assertNotSame($config, $config->withLogLevel(1));
        $this->assertNotSame($config, $config->withProxyCachePath('@tests/runtime'));
        $this->assertNotSame(
            $config,
            $config->withDispatcher(
                new EventDispatcherInterfaceProxy($dispatcherMock, new EventCollector(new TimelineCollector())),
            ),
        );
        $this->assertNotSame($config, $config->withDecoratedServices([
            LoggerInterface::class => [LoggerInterfaceProxy::class, LogCollector::class],
        ]));
    }

    public function testGetters(): void
    {
        $dispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $config = new ContainerProxyConfig(
            true,
            [
                LoggerInterface::class => [LoggerInterfaceProxy::class, LogCollector::class],
            ],
            $dispatcherMock,
            $this->createServiceCollector(),
            '@tests/runtime',
            1,
        );

        $this->assertTrue($config->getIsActive());
        $this->assertInstanceOf(EventDispatcherInterface::class, $config->getDispatcher());
        $this->assertInstanceOf(ServiceCollector::class, $config->getCollector());
        $this->assertEquals(1, $config->getLogLevel());
        $this->assertEquals('@tests/runtime', $config->getProxyCachePath());
        $this->assertEquals(
            [
                LoggerInterface::class => [LoggerInterfaceProxy::class, LogCollector::class],
            ],
            $config->getDecoratedServices(),
        );
        $this->assertEquals(
            [LoggerInterfaceProxy::class, LogCollector::class],
            $config->getDecoratedServiceConfig(LoggerInterface::class),
        );

        $this->assertTrue($config->hasCollector());
        $this->assertTrue($config->hasDispatcher());
        $this->assertTrue($config->hasDecoratedService(LoggerInterface::class));
        $this->assertSame(ServiceConfigType::ArrayDefinition, $config->getServiceConfigType(LoggerInterface::class));
    }

    private function createServiceCollector(): ServiceCollector
    {
        return new ServiceCollector(new TimelineCollector());
    }
}
