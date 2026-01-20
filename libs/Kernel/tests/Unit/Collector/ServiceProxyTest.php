<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use PHPUnit\Framework\TestCase;
use stdClass;
use AppDevPanel\Kernel\Collector\ContainerProxyConfig;
use AppDevPanel\Kernel\Collector\ServiceMethodProxy;
use AppDevPanel\Kernel\Collector\ServiceProxy;

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
