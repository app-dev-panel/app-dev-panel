<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector\Stream;

use AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector;
use AppDevPanel\Kernel\Collector\Stream\HttpStreamProxy;
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
}
