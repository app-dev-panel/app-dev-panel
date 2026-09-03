<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector;
use AppDevPanel\Kernel\Collector\Stream\HttpStreamProxy;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function in_array;
use function stream_get_wrappers;

final class HttpStreamCollectorTest extends AbstractCollectorTestCase
{
    /**
     * The proxy is driven directly with a `data:` URL: the real `StreamWrapper` opens it
     * through PHP's built-in RFC 2397 wrapper, so the whole HttpStreamProxy path (ignore
     * filtering, context inspection, collection) is exercised without DNS or sockets.
     */
    private const string URL = 'data://text/plain,hello-from-adp';

    /**
     * @param HttpStreamCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->collect(operation: 'read', path: __FILE__, args: ['arg1' => 'v1', 'arg2' => 'v2']);
        $collector->collect(operation: 'read', path: __FILE__, args: ['arg3' => 'v3', 'arg4' => 'v4']);
    }

    public function testStartupRegistersProxyAndShutdownRestoresBuiltInWrappers(): void
    {
        $collector = new HttpStreamCollector(ignoredUrls: ['ignored-host']);

        $collector->startup();
        try {
            $this->assertTrue(HttpStreamProxy::$registered);
            $this->assertSame($collector, HttpStreamProxy::$collector);
            $this->assertSame(['ignored-host'], HttpStreamProxy::$ignoredUrls);
            $this->assertTrue(in_array('http', stream_get_wrappers(), true));
        } finally {
            $collector->shutdown();
        }

        $this->assertFalse(HttpStreamProxy::$registered);
        $this->assertNull(HttpStreamProxy::$collector);
        $this->assertTrue(in_array('http', stream_get_wrappers(), true), 'Built-in http wrapper must be restored');

        // Collector is inactive after shutdown: further collect() calls are dropped.
        $collector->collect(operation: 'read', path: self::URL, args: []);
        $this->assertSame([], $collector->getCollected());
    }

    public function testReadIsCollectedOnceWithMethodAndHeadersFromContext(): void
    {
        $collector = new HttpStreamCollector();
        $collector->startup();
        try {
            $proxy = new HttpStreamProxy();
            $proxy->decorated->context = stream_context_create([
                'http' => ['method' => 'POST', 'header' => ['X-Test: 1', 'Accept: text/plain']],
            ]);

            $this->assertTrue($this->openThroughProxy($proxy, self::URL));
            $this->assertSame('hell', $proxy->stream_read(4));
            $this->assertSame('o-from-adp', $proxy->stream_read(1024));
            $this->assertTrue($proxy->stream_eof());
            $proxy->stream_close();

            $collected = $collector->getCollected();
        } finally {
            $collector->shutdown();
        }

        $this->assertSame(
            [
                'read' => [
                    [
                        'uri' => self::URL,
                        'args' => [
                            'method' => 'POST',
                            'response_headers' => [],
                            'request_headers' => ['X-Test: 1', 'Accept: text/plain'],
                        ],
                    ],
                ],
            ],
            $collected,
        );
        $this->assertSame(['http_stream' => ['read' => 1]], $collector->getSummary());
    }

    public function testReadWithoutContextDefaultsToGet(): void
    {
        $collector = new HttpStreamCollector();
        $collector->startup();
        try {
            $proxy = new HttpStreamProxy();

            $this->assertTrue($this->openThroughProxy($proxy, self::URL));
            $proxy->stream_read(4);
            $proxy->stream_close();

            $collected = $collector->getCollected();
        } finally {
            $collector->shutdown();
        }

        $this->assertCount(1, $collected['read']);
        $this->assertSame('GET', $collected['read'][0]['args']['method']);
        $this->assertSame([], $collected['read'][0]['args']['request_headers']);
    }

    #[DataProvider('dataIgnoredReads')]
    public function testReadIsNotCollectedWhenIgnored(
        array $ignoredPathPatterns,
        array $ignoredClasses,
        array $ignoredUrls,
    ): void {
        $collector = new HttpStreamCollector(
            ignoredPathPatterns: $ignoredPathPatterns,
            ignoredClasses: $ignoredClasses,
            ignoredUrls: $ignoredUrls,
        );
        $collector->startup();
        try {
            $proxy = new HttpStreamProxy();

            $this->assertTrue($this->openThroughProxy($proxy, self::URL));
            $this->assertTrue($proxy->ignored);
            $this->assertSame('hello', $proxy->stream_read(5));
            $proxy->stream_close();

            $collected = $collector->getCollected();
        } finally {
            $collector->shutdown();
        }

        $this->assertSame([], $collected);
        $this->assertSame(['http_stream' => []], $collector->getSummary());
    }

    public static function dataIgnoredReads(): iterable
    {
        yield 'ignored by calling file pattern' => [[basename(__FILE__, '.php')], [], []];
        yield 'ignored by calling class' => [[], [self::class], []];
        yield 'ignored by url pattern' => [[], [], ['hello-from-adp']];
    }

    public function testNothingIsCollectedWhenInactive(): void
    {
        $collector = new HttpStreamCollector();

        $collector->collect(operation: 'read', path: self::URL, args: []);

        $this->assertSame([], $collector->getCollected());
    }

    /**
     * Mirrors the frame layout of a real `fopen()` call site: BacktraceIgnoreMatcher inspects
     * frame 2 (calling file) and frame 3 (calling class) relative to `HttpStreamProxy::isIgnored()`.
     */
    private function openThroughProxy(HttpStreamProxy $proxy, string $url): bool
    {
        $openedPath = null;

        return $proxy->stream_open($url, 'r', 0, $openedPath);
    }

    protected function getCollector(): CollectorInterface
    {
        return new HttpStreamCollector();
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);
        $collected = $data;
        $this->assertCount(1, $collected);

        $this->assertCount(2, $collected['read']);
        $this->assertEquals(
            [
                ['uri' => __FILE__, 'args' => ['arg1' => 'v1', 'arg2' => 'v2']],
                ['uri' => __FILE__, 'args' => ['arg3' => 'v3', 'arg4' => 'v4']],
            ],
            $collected['read'],
        );
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);
        $this->assertArrayHasKey('http_stream', $data);
        $this->assertEquals(['read' => 2], $data['http_stream']);
    }
}
