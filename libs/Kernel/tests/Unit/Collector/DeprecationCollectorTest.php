<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\DeprecationCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class DeprecationCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|DeprecationCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        @trigger_error('Function foo() is deprecated, use bar() instead.', E_USER_DEPRECATED);
    }

    protected function getCollector(): CollectorInterface
    {
        return new DeprecationCollector(new TimelineCollector());
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);
        $this->assertCount(1, $data);

        $entry = $data[0];
        $this->assertArrayHasKey('time', $entry);
        $this->assertArrayHasKey('message', $entry);
        $this->assertArrayHasKey('file', $entry);
        $this->assertArrayHasKey('line', $entry);
        $this->assertArrayHasKey('category', $entry);
        $this->assertArrayHasKey('trace', $entry);

        $this->assertSame('Function foo() is deprecated, use bar() instead.', $entry['message']);
        $this->assertSame('user', $entry['category']);
        $this->assertIsFloat($entry['time']);
        $this->assertIsArray($entry['trace']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);
        $this->assertArrayHasKey('deprecation', $data);
        $this->assertSame(['total' => 1], $data['deprecation']);
    }

    public function testMultipleDeprecations(): void
    {
        $collector = new DeprecationCollector(new TimelineCollector());
        $collector->startup();

        @trigger_error('First deprecation', E_USER_DEPRECATED);
        @trigger_error('Second deprecation', E_USER_DEPRECATED);

        $data = $collector->getCollected();
        $collector->shutdown();

        $this->assertCount(2, $data);
        $this->assertSame('First deprecation', $data[0]['message']);
        $this->assertSame('Second deprecation', $data[1]['message']);
    }

    public function testTraceContainsFrames(): void
    {
        $collector = new DeprecationCollector(new TimelineCollector());
        $collector->startup();

        @trigger_error('trace test', E_USER_DEPRECATED);

        $data = $collector->getCollected();
        $collector->shutdown();

        $this->assertNotEmpty($data[0]['trace']);
        $frame = $data[0]['trace'][0];
        $this->assertArrayHasKey('file', $frame);
        $this->assertArrayHasKey('line', $frame);
        $this->assertArrayHasKey('function', $frame);
        $this->assertArrayHasKey('class', $frame);
    }

    public function testPreviousErrorHandlerPreserved(): void
    {
        $previousCalled = false;
        set_error_handler(function () use (&$previousCalled): bool {
            $previousCalled = true;
            return true;
        });

        $collector = new DeprecationCollector(new TimelineCollector());
        $collector->startup();

        @trigger_error('test deprecation', E_USER_DEPRECATED);

        $collector->shutdown();

        $this->assertTrue($previousCalled);

        // Restore our test handler
        restore_error_handler();
    }

    public function testShutdownResetsData(): void
    {
        $collector = new DeprecationCollector(new TimelineCollector());
        $collector->startup();

        @trigger_error('before shutdown', E_USER_DEPRECATED);
        $this->assertCount(1, $collector->getCollected());

        $collector->shutdown();

        // After shutdown, collector is inactive
        $this->assertSame([], $collector->getCollected());

        // After restart, data is fresh
        $collector->startup();
        $this->assertSame([], $collector->getCollected());
        $collector->shutdown();
    }
}
