<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Helper\StreamWrapper;

use AppDevPanel\Kernel\Tests\Support\Stub\PhpStreamProxy;
use PHPUnit\Framework\TestCase;

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

    public function testStreamOpenReadWrite(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $tmpFile = tempnam(sys_get_temp_dir(), 'sw-test-');
        file_put_contents($tmpFile, 'hello world');

        try {
            $opened = null;
            $result = $wrapper->stream_open($tmpFile, 'r', 0, $opened);
            $this->assertTrue($result);

            $content = $wrapper->stream_read(5);
            $this->assertSame('hello', $content);

            $this->assertSame(5, $wrapper->stream_tell());
            $this->assertFalse($wrapper->stream_eof());

            $wrapper->stream_seek(0);
            $this->assertSame(0, $wrapper->stream_tell());

            $stat = $wrapper->stream_stat();
            $this->assertIsArray($stat);

            $wrapper->stream_close();
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testStreamOpenWithOpenedPath(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $tmpFile = tempnam(sys_get_temp_dir(), 'sw-test-');
        file_put_contents($tmpFile, 'test');

        try {
            $openedPath = '';
            $result = $wrapper->stream_open($tmpFile, 'r', 0, $openedPath);
            $this->assertTrue($result);
            $this->assertNotEmpty($openedPath);

            $wrapper->stream_close();
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testStreamWriteAndFlush(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $tmpFile = tempnam(sys_get_temp_dir(), 'sw-test-');

        try {
            $opened = null;
            $wrapper->stream_open($tmpFile, 'w', 0, $opened);

            $written = $wrapper->stream_write('test data');
            $this->assertSame(9, $written);

            $this->assertTrue($wrapper->stream_flush());

            $wrapper->stream_close();

            $this->assertSame('test data', file_get_contents($tmpFile));
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testStreamTruncate(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $tmpFile = tempnam(sys_get_temp_dir(), 'sw-test-');
        file_put_contents($tmpFile, 'long content here');

        try {
            $opened = null;
            $wrapper->stream_open($tmpFile, 'r+', 0, $opened);

            $this->assertTrue($wrapper->stream_truncate(4));

            $wrapper->stream_close();

            $this->assertSame('long', file_get_contents($tmpFile));
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testStreamLockDefaultsToExclusive(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $tmpFile = tempnam(sys_get_temp_dir(), 'sw-test-');
        file_put_contents($tmpFile, 'test');

        try {
            $opened = null;
            $wrapper->stream_open($tmpFile, 'r', 0, $opened);

            // operation=0 should default to LOCK_EX
            $this->assertTrue($wrapper->stream_lock(0));

            $wrapper->stream_close();
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testStreamCastReturnsResource(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $tmpFile = tempnam(sys_get_temp_dir(), 'sw-test-');
        file_put_contents($tmpFile, 'test');

        try {
            $opened = null;
            $wrapper->stream_open($tmpFile, 'r', 0, $opened);

            $result = $wrapper->stream_cast(STREAM_CAST_AS_STREAM);
            $this->assertIsResource($result);

            $wrapper->stream_close();
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testStreamCastReturnsFalseWithNoStream(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $this->assertFalse($wrapper->stream_cast(STREAM_CAST_AS_STREAM));
    }

    public function testDirOpenAndReadAndClose(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $tmpDir = sys_get_temp_dir() . '/sw-test-dir-' . uniqid();
        mkdir($tmpDir);
        file_put_contents($tmpDir . '/file1.txt', 'a');
        file_put_contents($tmpDir . '/file2.txt', 'b');

        try {
            $this->assertTrue($wrapper->dir_opendir($tmpDir, 0));
            $this->assertSame($tmpDir, $wrapper->filename);

            $entries = [];
            while (($entry = $wrapper->dir_readdir()) !== false) {
                if ($entry !== '.' && $entry !== '..') {
                    $entries[] = $entry;
                }
            }

            sort($entries);
            $this->assertSame(['file1.txt', 'file2.txt'], $entries);

            $this->assertTrue($wrapper->dir_rewinddir());

            $wrapper->dir_closedir();
        } finally {
            @unlink($tmpDir . '/file1.txt');
            @unlink($tmpDir . '/file2.txt');
            @rmdir($tmpDir);
        }
    }

    public function testDirRewinddirReturnsFalseWithNoStream(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $this->assertFalse($wrapper->dir_rewinddir());
    }

    public function testMkdirAndRmdir(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $tmpDir = sys_get_temp_dir() . '/sw-test-mkdir-' . uniqid();

        try {
            $this->assertTrue($wrapper->mkdir($tmpDir, 0o755, 0));
            $this->assertDirectoryExists($tmpDir);

            $this->assertTrue($wrapper->rmdir($tmpDir, 0));
            $this->assertDirectoryDoesNotExist($tmpDir);
        } finally {
            @rmdir($tmpDir);
        }
    }

    public function testMkdirRecursive(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $tmpDir = sys_get_temp_dir() . '/sw-test-mkdir-r-' . uniqid() . '/sub/deep';

        try {
            $this->assertTrue($wrapper->mkdir($tmpDir, 0o755, STREAM_MKDIR_RECURSIVE));
            $this->assertDirectoryExists($tmpDir);
        } finally {
            @rmdir($tmpDir);
            @rmdir(dirname($tmpDir));
            @rmdir(dirname($tmpDir, 2));
        }
    }

    public function testRename(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $src = tempnam(sys_get_temp_dir(), 'sw-rename-');
        $dst = $src . '.renamed';
        file_put_contents($src, 'rename me');

        try {
            $this->assertTrue($wrapper->rename($src, $dst));
            $this->assertFileDoesNotExist($src);
            $this->assertSame('rename me', file_get_contents($dst));
        } finally {
            @unlink($src);
            @unlink($dst);
        }
    }

    public function testUnlink(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $tmpFile = tempnam(sys_get_temp_dir(), 'sw-unlink-');
        file_put_contents($tmpFile, 'delete me');

        $this->assertTrue($wrapper->unlink($tmpFile));
        $this->assertFileDoesNotExist($tmpFile);
    }

    public function testUrlStatExistingFile(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $result = $wrapper->url_stat(__FILE__, 0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('size', $result);
    }

    public function testUrlStatNonExistentQuiet(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $result = $wrapper->url_stat('/nonexistent/path/file.php', STREAM_URL_STAT_QUIET);

        $this->assertFalse($result);
    }

    public function testStreamMetadataTouch(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $tmpFile = tempnam(sys_get_temp_dir(), 'sw-meta-');

        try {
            $result = $wrapper->stream_metadata($tmpFile, STREAM_META_TOUCH, [time(), time()]);
            $this->assertTrue($result);
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testStreamMetadataChmod(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $tmpFile = tempnam(sys_get_temp_dir(), 'sw-meta-');

        try {
            $result = $wrapper->stream_metadata($tmpFile, STREAM_META_ACCESS, 0o644);
            $this->assertTrue($result);
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testStreamSetOptionDefault(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $tmpFile = tempnam(sys_get_temp_dir(), 'sw-opt-');
        file_put_contents($tmpFile, 'test');

        try {
            $opened = null;
            $wrapper->stream_open($tmpFile, 'r', 0, $opened);

            $result = $wrapper->stream_set_option(999, 0, 0);
            $this->assertFalse($result);

            $wrapper->stream_close();
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testStreamCloseWithNullStream(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        // stream is null by default, close should not throw
        $wrapper->stream_close();
        $this->assertNull($wrapper->stream);
    }

    public function testStreamMetadataDefaultReturnsFalse(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $result = $wrapper->stream_metadata('/tmp/dummy', 999, null);
        $this->assertFalse($result);
    }

    public function testStreamOpenNonExistentFileReturnsFalse(): void
    {
        $wrapper = new \AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper();

        $opened = null;
        $result = @$wrapper->stream_open('/nonexistent/path/file.txt', 'r', 0, $opened);

        $this->assertFalse($result);
    }
}
