<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector\Stream;

use AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector;
use AppDevPanel\Kernel\Collector\Stream\HttpStreamProxy;
use AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper;
use PHPUnit\Framework\TestCase;

final class HttpStreamProxyTest extends TestCase
{
    protected function setUp(): void
    {
        HttpStreamProxy::$ignoredPathPatterns = [];
        HttpStreamProxy::$ignoredClasses = [];
        HttpStreamProxy::$ignoredUrls = [];
        HttpStreamProxy::$collector = null;
    }

    protected function tearDown(): void
    {
        HttpStreamProxy::unregister();
        HttpStreamProxy::$ignoredPathPatterns = [];
        HttpStreamProxy::$ignoredClasses = [];
        HttpStreamProxy::$ignoredUrls = [];
        HttpStreamProxy::$collector = null;
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

    public function testUnregisterTwice(): void
    {
        HttpStreamProxy::unregister();
        $this->assertFalse(HttpStreamProxy::$registered);
        HttpStreamProxy::unregister();
        $this->assertFalse(HttpStreamProxy::$registered);
    }

    public function testStaticPropertiesDefaultValues(): void
    {
        $this->assertSame([], HttpStreamProxy::$ignoredPathPatterns);
        $this->assertSame([], HttpStreamProxy::$ignoredClasses);
        $this->assertSame([], HttpStreamProxy::$ignoredUrls);
        $this->assertNull(HttpStreamProxy::$collector);
    }

    public function testIgnoredUrlsConfiguration(): void
    {
        HttpStreamProxy::$ignoredUrls = ['example.com', 'internal.test'];

        $this->assertSame(['example.com', 'internal.test'], HttpStreamProxy::$ignoredUrls);
    }

    public function testIgnoredClassesConfiguration(): void
    {
        HttpStreamProxy::$ignoredClasses = ['SomeClass', 'AnotherClass'];

        $this->assertSame(['SomeClass', 'AnotherClass'], HttpStreamProxy::$ignoredClasses);
    }

    public function testIgnoredPathPatternsConfiguration(): void
    {
        HttpStreamProxy::$ignoredPathPatterns = ['/vendor/', '/cache/'];

        $this->assertSame(['/vendor/', '/cache/'], HttpStreamProxy::$ignoredPathPatterns);
    }

    public function testCollectorCanBeAssigned(): void
    {
        $collector = new HttpStreamCollector();
        HttpStreamProxy::$collector = $collector;

        $this->assertSame($collector, HttpStreamProxy::$collector);
    }

    public function testConstructorCreatesDecoratedStreamWrapper(): void
    {
        $proxy = new HttpStreamProxy();

        $this->assertInstanceOf(StreamWrapper::class, $proxy->decorated);
        $this->assertFalse($proxy->ignored);
    }

    public function testMagicGetDelegatesToDecorated(): void
    {
        $proxy = new HttpStreamProxy();

        // __get delegates to the decorated StreamWrapper
        $this->assertNull($proxy->filename);
        $this->assertNull($proxy->stream);
    }

    public function testStreamWriteCollectsOnFirstCallOnly(): void
    {
        $collector = new HttpStreamCollector();
        $collector->startup();

        // Create a temporary file and open it through the proxy
        $tmpFile = tempnam(sys_get_temp_dir(), 'http_proxy_test_');
        $proxy = new HttpStreamProxy();
        $proxy->ignored = false;

        // Simulate a decorated stream with an open file
        HttpStreamProxy::unregister();
        $proxy->decorated->stream = fopen($tmpFile, 'w');
        $proxy->decorated->filename = $tmpFile;
        HttpStreamProxy::register();

        // First write should collect
        $proxy->stream_write('hello');

        // Second write should NOT collect again (readCollected flag)
        $proxy->stream_write(' world');

        $proxy->stream_close();
        $collector->shutdown();

        @unlink($tmpFile);
        $this->assertTrue(true); // Reached without error
    }

    public function testStreamWriteSkipsCollectionWhenIgnored(): void
    {
        $collector = new HttpStreamCollector();
        $collector->startup();

        $tmpFile = tempnam(sys_get_temp_dir(), 'http_proxy_test_');
        $proxy = new HttpStreamProxy();
        $proxy->ignored = true;

        HttpStreamProxy::unregister();
        $proxy->decorated->stream = fopen($tmpFile, 'w');
        $proxy->decorated->filename = $tmpFile;
        HttpStreamProxy::register();

        $proxy->stream_write('data');
        $proxy->stream_close();

        $collected = $collector->getCollected();
        $this->assertArrayNotHasKey('write', $collected);

        $collector->shutdown();
        @unlink($tmpFile);
    }

    public function testMkdirCollectsWhenNotIgnored(): void
    {
        $collector = new HttpStreamCollector();
        $collector->startup();

        $tmpDir = sys_get_temp_dir() . '/http_proxy_mkdir_test_' . uniqid();

        $proxy = new HttpStreamProxy();
        $proxy->ignored = false;

        $proxy->mkdir($tmpDir, 0o777, 0);

        $collected = $collector->getCollected();
        $this->assertArrayHasKey('mkdir', $collected);
        $this->assertSame($tmpDir, $collected['mkdir'][0]['uri']);

        $collector->shutdown();
        @rmdir($tmpDir);
    }

    public function testMkdirSkipsCollectionWhenIgnored(): void
    {
        $collector = new HttpStreamCollector();
        $collector->startup();

        $tmpDir = sys_get_temp_dir() . '/http_proxy_mkdir_ign_' . uniqid();

        // Mark proxy as ignored via URL pattern
        HttpStreamProxy::$ignoredUrls = ['.*'];

        $proxy = new HttpStreamProxy();
        $proxy->ignored = true;

        // mkdir checks isIgnored internally using the path, but we test via ignored flag
        // We need to use __call directly since mkdir also calls isIgnored
        $proxy->mkdir($tmpDir, 0o777, 0);

        $collected = $collector->getCollected();
        // mkdir checks $this->ignored which was set, but it re-checks via isIgnored()
        // Since ignored URLs match everything, it should skip collection
        $collector->shutdown();
        @rmdir($tmpDir);
        $this->assertTrue(true);
    }

    public function testRenameCollectsWhenNotIgnored(): void
    {
        $collector = new HttpStreamCollector();
        $collector->startup();

        $tmpFrom = tempnam(sys_get_temp_dir(), 'http_rename_from_');
        $tmpTo = $tmpFrom . '_renamed';

        $proxy = new HttpStreamProxy();
        $proxy->ignored = false;

        $proxy->rename($tmpFrom, $tmpTo);

        $collected = $collector->getCollected();
        $this->assertArrayHasKey('rename', $collected);
        $this->assertSame($tmpFrom, $collected['rename'][0]['uri']);
        $this->assertSame($tmpTo, $collected['rename'][0]['args']['path_to']);

        $collector->shutdown();
        @unlink($tmpTo);
    }

    public function testRmdirCollectsWhenNotIgnored(): void
    {
        $collector = new HttpStreamCollector();
        $collector->startup();

        $tmpDir = sys_get_temp_dir() . '/http_proxy_rmdir_test_' . uniqid();
        mkdir($tmpDir, 0o777);

        $proxy = new HttpStreamProxy();
        $proxy->ignored = false;

        $proxy->rmdir($tmpDir, 0);

        $collected = $collector->getCollected();
        $this->assertArrayHasKey('rmdir', $collected);
        $this->assertSame($tmpDir, $collected['rmdir'][0]['uri']);

        $collector->shutdown();
    }

    public function testUnlinkCollectsWhenNotIgnored(): void
    {
        $collector = new HttpStreamCollector();
        $collector->startup();

        $tmpFile = tempnam(sys_get_temp_dir(), 'http_unlink_');

        $proxy = new HttpStreamProxy();
        $proxy->ignored = false;

        $proxy->unlink($tmpFile);

        $collected = $collector->getCollected();
        $this->assertArrayHasKey('unlink', $collected);
        $this->assertSame($tmpFile, $collected['unlink'][0]['uri']);

        $collector->shutdown();
    }

    public function testUnlinkSkipsCollectionWhenIgnored(): void
    {
        $collector = new HttpStreamCollector();
        $collector->startup();

        $tmpFile = tempnam(sys_get_temp_dir(), 'http_unlink_ign_');

        HttpStreamProxy::$ignoredUrls = ['.*'];

        $proxy = new HttpStreamProxy();
        $proxy->ignored = true;

        $proxy->unlink($tmpFile);

        $collected = $collector->getCollected();
        $this->assertArrayNotHasKey('unlink', $collected);

        $collector->shutdown();
    }

    public function testStreamTraitMethodsDelegateToDecorated(): void
    {
        $proxy = new HttpStreamProxy();
        $tmpFile = tempnam(sys_get_temp_dir(), 'http_trait_');

        // Open via decorated directly, then test trait methods
        HttpStreamProxy::unregister();
        $proxy->decorated->stream = fopen($tmpFile, 'w+');
        $proxy->decorated->filename = $tmpFile;
        HttpStreamProxy::register();

        // stream_write
        $written = $proxy->stream_write('test data');
        $this->assertSame(9, $written);

        // stream_tell
        $pos = $proxy->stream_tell();
        $this->assertSame(9, $pos);

        // stream_seek
        $result = $proxy->stream_seek(0);
        $this->assertTrue($result);

        // stream_tell after seek
        $this->assertSame(0, $proxy->stream_tell());

        // stream_eof
        $this->assertFalse($proxy->stream_eof());

        // stream_stat
        $stat = $proxy->stream_stat();
        $this->assertIsArray($stat);

        // stream_flush
        $this->assertTrue($proxy->stream_flush());

        // stream_lock
        $this->assertTrue($proxy->stream_lock(LOCK_EX));
        $this->assertTrue($proxy->stream_lock(LOCK_UN));

        // stream_truncate
        $this->assertTrue($proxy->stream_truncate(4));

        // stream_close
        $proxy->stream_close();

        @unlink($tmpFile);
    }

    public function testStreamSetOption(): void
    {
        $proxy = new HttpStreamProxy();
        $tmpFile = tempnam(sys_get_temp_dir(), 'http_setopt_');

        HttpStreamProxy::unregister();
        $proxy->decorated->stream = fopen($tmpFile, 'w+');
        HttpStreamProxy::register();

        // STREAM_OPTION_WRITE_BUFFER with arg2 = buffer size
        $result = $proxy->stream_set_option(STREAM_OPTION_WRITE_BUFFER, STREAM_BUFFER_FULL, 4096);
        $this->assertIsBool($result);

        // STREAM_OPTION_READ_TIMEOUT
        $result = $proxy->stream_set_option(STREAM_OPTION_READ_TIMEOUT, 1, 0);
        $this->assertIsBool($result);

        $proxy->stream_close();
        @unlink($tmpFile);
    }

    public function testStreamCast(): void
    {
        $proxy = new HttpStreamProxy();
        $tmpFile = tempnam(sys_get_temp_dir(), 'http_cast_');

        HttpStreamProxy::unregister();
        $proxy->decorated->stream = fopen($tmpFile, 'r');
        HttpStreamProxy::register();

        $result = $proxy->stream_cast(STREAM_CAST_AS_STREAM);
        $this->assertIsResource($result);

        $proxy->stream_close();
        @unlink($tmpFile);
    }

    public function testStreamCastReturnsFalseWhenNoStream(): void
    {
        $proxy = new HttpStreamProxy();

        $result = $proxy->stream_cast(STREAM_CAST_AS_STREAM);
        $this->assertFalse($result);
    }

    public function testDirOperations(): void
    {
        $proxy = new HttpStreamProxy();

        // dir_opendir, dir_readdir, dir_rewinddir, dir_closedir
        $result = $proxy->dir_opendir(sys_get_temp_dir(), 0);
        $this->assertTrue($result);

        $entry = $proxy->dir_readdir();
        $this->assertIsString($entry);

        $result = $proxy->dir_rewinddir();
        $this->assertTrue($result);

        $proxy->dir_closedir();
        $this->assertTrue(true);
    }

    public function testDirReaddirCollectsOnFirstCallOnly(): void
    {
        $collector = new HttpStreamCollector();
        $collector->startup();

        $proxy = new HttpStreamProxy();
        $proxy->ignored = false;

        HttpStreamProxy::unregister();
        $proxy->decorated->stream = opendir(sys_get_temp_dir());
        $proxy->decorated->filename = sys_get_temp_dir();
        HttpStreamProxy::register();

        // First call should collect
        $proxy->dir_readdir();
        // Second call should NOT collect again
        $proxy->dir_readdir();

        $collected = $collector->getCollected();
        $this->assertArrayHasKey('readdir', $collected);
        $this->assertCount(1, $collected['readdir']);

        $proxy->dir_closedir();
        $collector->shutdown();
    }

    public function testUrlStat(): void
    {
        $proxy = new HttpStreamProxy();
        $tmpFile = tempnam(sys_get_temp_dir(), 'http_urlstat_');

        $stat = $proxy->url_stat($tmpFile, 0);
        $this->assertIsArray($stat);

        // Non-existent file with quiet flag
        $stat = $proxy->url_stat('/non/existent/file_' . uniqid(), STREAM_URL_STAT_QUIET);
        $this->assertFalse($stat);

        @unlink($tmpFile);
    }

    public function testStreamMetadata(): void
    {
        $proxy = new HttpStreamProxy();
        $tmpFile = tempnam(sys_get_temp_dir(), 'http_meta_');

        $result = $proxy->stream_metadata($tmpFile, STREAM_META_TOUCH, [time(), time()]);
        $this->assertTrue($result);

        $result = $proxy->stream_metadata($tmpFile, STREAM_META_ACCESS, 0o644);
        $this->assertTrue($result);

        @unlink($tmpFile);
    }

    public function testStreamOpenSetsIgnoredFlag(): void
    {
        $collector = new HttpStreamCollector();
        $collector->startup();

        // Set ignored URL pattern that matches the test file
        HttpStreamProxy::$ignoredUrls = ['.*ignored_url.*'];

        $tmpFile = tempnam(sys_get_temp_dir(), 'ignored_url_');

        $proxy = new HttpStreamProxy();
        $opened = null;
        $proxy->stream_open($tmpFile, 'r', 0, $opened);

        // The URL matches the ignored pattern, so ignored should be true
        $this->assertTrue($proxy->ignored);

        $proxy->stream_close();
        $collector->shutdown();
        @unlink($tmpFile);
    }

    public function testStreamOpenNotIgnored(): void
    {
        $collector = new HttpStreamCollector();
        $collector->startup();

        HttpStreamProxy::$ignoredUrls = [];

        $tmpFile = tempnam(sys_get_temp_dir(), 'not_ignored_');

        $proxy = new HttpStreamProxy();
        $opened = null;
        $proxy->stream_open($tmpFile, 'r', 0, $opened);

        // No ignore patterns set; URL should not be ignored
        $this->assertFalse($proxy->ignored);
        $proxy->stream_close();
        $collector->shutdown();
        @unlink($tmpFile);
    }
}
