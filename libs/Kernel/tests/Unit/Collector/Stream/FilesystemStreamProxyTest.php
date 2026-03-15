<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector\Stream;

use AppDevPanel\Kernel\Collector\Stream\FilesystemStreamProxy;
use PHPUnit\Framework\TestCase;

final class FilesystemStreamProxyTest extends TestCase
{
    protected function tearDown(): void
    {
        FilesystemStreamProxy::unregister();
    }

    public function testRegisteredTwice(): void
    {
        FilesystemStreamProxy::unregister();
        $this->assertFalse(FilesystemStreamProxy::$registered);
        FilesystemStreamProxy::register();
        $this->assertTrue(FilesystemStreamProxy::$registered);
        FilesystemStreamProxy::register();
        $this->assertTrue(FilesystemStreamProxy::$registered);
    }

    public function testProxyAccess(): void
    {
        $proxy = new FilesystemStreamProxy();
        FilesystemStreamProxy::register();
        $handle = opendir(sys_get_temp_dir());

        $firstElement = readdir($handle);
        $secondElement = readdir($handle);

        $this->assertNotSame($firstElement, $secondElement);
        rewinddir($handle);
        $this->assertEquals($firstElement, readdir($handle));

        $proxy->decorated->stream = $handle;
        $proxy->dir_rewinddir();

        $this->assertEquals($firstElement, readdir($handle));
    }
}
