<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Proxy;

use AppDevPanel\Adapter\Yii3\Proxy\ContainerProxyConfig;
use AppDevPanel\Adapter\Yii3\Proxy\ServiceMethodProxy;
use AppDevPanel\Adapter\Yii3\Proxy\ServiceProxy;
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
