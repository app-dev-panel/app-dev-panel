<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Tests\Unit;

use AppDevPanel\Adapter\Yiisoft\DebugServiceProvider;
use AppDevPanel\Adapter\Yiisoft\Proxy\ContainerInterfaceProxy;
use AppDevPanel\Adapter\Yiisoft\Proxy\ContainerProxyConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;

final class DebugServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset the static $resolving flag between tests
        $reflection = new ReflectionClass(DebugServiceProvider::class);
        $property = $reflection->getProperty('resolving');
        $property->setValue(null, false);
    }

    public function testGetDefinitionsReturnsExpectedKeys(): void
    {
        $provider = new DebugServiceProvider();
        $definitions = $provider->getDefinitions();

        $this->assertArrayHasKey(ContainerInterface::class, $definitions);
        $this->assertIsCallable($definitions[ContainerInterface::class]);
    }

    public function testGetExtensionsReturnsEmptyArray(): void
    {
        $provider = new DebugServiceProvider();

        $this->assertSame([], $provider->getExtensions());
    }

    public function testFactoryReturnsContainerInterfaceProxy(): void
    {
        $provider = new DebugServiceProvider();
        $definitions = $provider->getDefinitions();
        $factory = $definitions[ContainerInterface::class];

        $config = new ContainerProxyConfig();

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with(ContainerProxyConfig::class)->willReturn($config);

        $result = $factory($container);

        $this->assertInstanceOf(ContainerInterfaceProxy::class, $result);
    }

    public function testRecursionGuardReturnsRawContainer(): void
    {
        $provider = new DebugServiceProvider();
        $definitions = $provider->getDefinitions();
        $factory = $definitions[ContainerInterface::class];

        // Simulate a container where resolving ContainerProxyConfig triggers
        // a recursive resolution of ContainerInterface.
        $innerContainer = $this->createMock(ContainerInterface::class);
        $capturedFactory = $factory;
        $innerContainer
            ->method('get')
            ->with(ContainerProxyConfig::class)
            ->willReturnCallback(function () use ($capturedFactory, $innerContainer) {
                // This recursive call should hit the guard and return $innerContainer
                $recursiveResult = $capturedFactory($innerContainer);
                $this->assertNotInstanceOf(
                    ContainerInterfaceProxy::class,
                    $recursiveResult,
                    'Recursive resolution should return the raw container, not a proxy',
                );
                $this->assertSame($innerContainer, $recursiveResult);

                // Return a valid config so the outer call can complete
                return new ContainerProxyConfig();
            });

        $result = $factory($innerContainer);

        // The outer call should still produce a proxy
        $this->assertInstanceOf(ContainerInterfaceProxy::class, $result);
    }

    public function testResolvingFlagIsResetAfterException(): void
    {
        $provider = new DebugServiceProvider();
        $definitions = $provider->getDefinitions();
        $factory = $definitions[ContainerInterface::class];

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->with(ContainerProxyConfig::class)
            ->willThrowException(new \RuntimeException('Config not found'));

        $thrown = false;
        try {
            $factory($container);
        } catch (\RuntimeException $e) {
            $thrown = true;
            $this->assertSame('Config not found', $e->getMessage());
        }
        $this->assertTrue($thrown, 'Expected RuntimeException to be thrown');

        // After the exception, the flag should be reset, so a subsequent call should work
        $workingContainer = $this->createMock(ContainerInterface::class);
        $workingContainer->method('get')->with(ContainerProxyConfig::class)->willReturn(new ContainerProxyConfig());

        $result = $factory($workingContainer);
        $this->assertInstanceOf(ContainerInterfaceProxy::class, $result);
    }
}
