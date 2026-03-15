<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector\Stream;

use AppDevPanel\Kernel\Collector\Stream\HttpStreamProxy;
use PHPUnit\Framework\TestCase;

final class HttpStreamProxyTest extends TestCase
{
    protected function tearDown(): void
    {
        HttpStreamProxy::unregister();
    }

    public function testRegisteredTwice(): void
    {
        HttpStreamProxy::unregister();
        $this->assertFalse(HttpStreamProxy::$registered);
        HttpStreamProxy::register();
        $this->assertTrue(HttpStreamProxy::$registered);
        HttpStreamProxy::register();
        $this->assertTrue(HttpStreamProxy::$registered);
    }
}
