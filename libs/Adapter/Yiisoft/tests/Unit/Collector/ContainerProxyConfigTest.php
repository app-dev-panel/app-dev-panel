<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Collector;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Yii\Debug\Collector\ContainerProxyConfig;
use Yiisoft\Yii\Debug\Collector\EventCollector;
use Yiisoft\Yii\Debug\Collector\EventDispatcherInterfaceProxy;
use Yiisoft\Yii\Debug\Collector\LogCollector;
use Yiisoft\Yii\Debug\Collector\LoggerInterfaceProxy;
use Yiisoft\Yii\Debug\Collector\ServiceCollector;
use Yiisoft\Yii\Debug\Collector\TimelineCollector;

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
                new EventDispatcherInterfaceProxy($dispatcherMock, new EventCollector(new TimelineCollector()))
            )
        );
        $this->assertNotSame(
            $config,
            $config->withDecoratedServices(
                [
                    LoggerInterface::class => [LoggerInterfaceProxy::class, LogCollector::class],
                ]
            )
        );
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
            1
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
            $config->getDecoratedServices()
        );
        $this->assertEquals(
            [LoggerInterfaceProxy::class, LogCollector::class],
            $config->getDecoratedServiceConfig(LoggerInterface::class)
        );

        $this->assertTrue($config->hasCollector());
        $this->assertTrue($config->hasDispatcher());
        $this->assertTrue($config->hasDecoratedService(LoggerInterface::class));
        $this->assertTrue($config->hasDecoratedServiceArrayConfig(LoggerInterface::class));
        $this->assertFalse($config->hasDecoratedServiceArrayConfigWithStringKeys(LoggerInterface::class));
        $this->assertFalse($config->hasDecoratedServiceCallableConfig(LoggerInterface::class));
    }

    protected function createServiceCollector(): ServiceCollector
    {
        return new ServiceCollector(new TimelineCollector());
    }
}
