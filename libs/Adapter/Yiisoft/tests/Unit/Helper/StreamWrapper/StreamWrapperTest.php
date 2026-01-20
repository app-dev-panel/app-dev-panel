<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Helper\StreamWrapper;

use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Debug\Tests\Support\Stub\PhpStreamProxy;

final class StreamWrapperTest extends TestCase
{
    protected function tearDown(): void
    {
        PhpStreamProxy::unregister();
    }

    public function testSeekStream(): void
    {
        $handle = fopen('php://memory', 'rw');

        PhpStreamProxy::register();
        $proxy = new PhpStreamProxy();
        $proxy->decorated->stream = $handle;

        fwrite($handle, '1234567890');

        fseek($handle, 0);

        $firstElement = fread($handle, 2);
        $secondElement = fread($handle, 2);

        $this->assertNotSame($firstElement, $secondElement);

        fseek($handle, 0);

        $this->assertEquals($firstElement, fread($handle, 2));

        $proxy->stream_seek(0);
        $this->assertEquals($firstElement, fread($handle, 2));
    }

    public function testLockStream(): void
    {
        $handle = fopen('php://memory', 'rw');

        PhpStreamProxy::register();
        $proxy = new PhpStreamProxy();
        $proxy->decorated->stream = $handle;

        fwrite($handle, '1234567890');

        fseek($handle, 0);

        $firstElement = fread($handle, 2);

        flock($handle, LOCK_EX);
        fwrite($handle, '1234567890');
        fseek($handle, 0);

        $this->assertEquals($firstElement, fread($handle, 2));

        $proxy->stream_seek(0);
        $this->assertEquals($firstElement, fread($handle, 2));
    }
}
