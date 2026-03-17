<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Tests\Unit\Proxy;

use AppDevPanel\Adapter\Yiisoft\Proxy\ContainerProxyConfig;
use AppDevPanel\Adapter\Yiisoft\Proxy\ServiceMethodProxy;
use AppDevPanel\Adapter\Yiisoft\Proxy\ServiceProxy;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ServiceProxyTest extends TestCase
{
    public function testServiceProxy(): void
    {
        $object = new ServiceProxy('test', new stdClass(), new ContainerProxyConfig());
        $this->assertIsObject($object);
    }

    public function testServiceMethodProxy(): void
    {
        $object = new ServiceMethodProxy('test', new stdClass(), ['__toString'], new ContainerProxyConfig());
        $this->assertIsObject($object);
    }
}
