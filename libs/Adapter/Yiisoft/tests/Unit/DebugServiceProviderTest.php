<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Yii\Debug\Collector\ContainerInterfaceProxy;
use Yiisoft\Yii\Debug\DebugServiceProvider;

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
