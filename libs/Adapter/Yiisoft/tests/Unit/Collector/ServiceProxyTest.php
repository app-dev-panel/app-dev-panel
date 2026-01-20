<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Collector;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Yii\Debug\Collector\ContainerProxyConfig;
use Yiisoft\Yii\Debug\Collector\ServiceMethodProxy;
use Yiisoft\Yii\Debug\Collector\ServiceProxy;

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
