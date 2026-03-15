<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit;

use AppDevPanel\Adapter\Yiisoft\DebugServiceProvider;
use AppDevPanel\Kernel\Collector\ContainerInterfaceProxy;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

final class DebugServiceProviderTest extends TestCase
{
    public function testRegister(): void
    {
        $config = ContainerConfig::create()->withProviders([
            new DebugServiceProvider(),
        ]);
        $container = new Container($config);

        $this->assertInstanceOf(ContainerInterfaceProxy::class, $container->get(ContainerInterface::class));
    }
}
