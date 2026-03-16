<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Collector;

use AppDevPanel\Adapter\Symfony\Collector\SymfonyExceptionCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class SymfonyExceptionCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new SymfonyExceptionCollector(new TimelineCollector());
    }

    /**
     * @param CollectorInterface|SymfonyExceptionCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $exception = new \RuntimeException('test error', 500, new \LogicException('previous', 42));
        $collector->collect($exception);
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);
        $this->assertCount(2, $data);

        foreach ($data as $exception) {
            $this->assertArrayHasKey('class', $exception);
            $this->assertArrayHasKey('message', $exception);
            $this->assertArrayHasKey('file', $exception);
            $this->assertArrayHasKey('line', $exception);
            $this->assertArrayHasKey('code', $exception);
            $this->assertArrayHasKey('trace', $exception);
            $this->assertArrayHasKey('traceAsString', $exception);
        }

        $this->assertSame(\RuntimeException::class, $data[0]['class']);
        $this->assertSame('test error', $data[0]['message']);
        $this->assertSame(500, $data[0]['code']);

        $this->assertSame(\LogicException::class, $data[1]['class']);
        $this->assertSame('previous', $data[1]['message']);
        $this->assertSame(42, $data[1]['code']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);
        $this->assertArrayHasKey('exception', $data);

        $exception = $data['exception'];
        $this->assertSame(\RuntimeException::class, $exception['class']);
        $this->assertSame('test error', $exception['message']);
        $this->assertSame(500, $exception['code']);
    }

    public function testNoExceptionCollected(): void
    {
        $collector = new SymfonyExceptionCollector(new TimelineCollector());
        $collector->startup();

        $this->assertSame([], $collector->getCollected());
        $this->assertSame([], $collector->getSummary());
    }
}
