<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Collector;

use Exception;
use Yiisoft\ErrorHandler\Event\ApplicationError;
use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Collector\ExceptionCollector;
use Yiisoft\Yii\Debug\Collector\TimelineCollector;
use Yiisoft\Yii\Debug\Tests\Shared\AbstractCollectorTestCase;

final class ExceptionCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|ExceptionCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $exception = new Exception('test', 777, new Exception('previous', 666));
        $collector->collect(new ApplicationError($exception));
    }

    protected function getCollector(): CollectorInterface
    {
        return new ExceptionCollector(new TimelineCollector());
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

        $exception = $data[0];
        $this->assertEquals(Exception::class, $exception['class']);
        $this->assertEquals('test', $exception['message']);
        $this->assertEquals(777, $exception['code']);

        $exception = $data[1];
        $this->assertEquals(Exception::class, $exception['class']);
        $this->assertEquals('previous', $exception['message']);
        $this->assertEquals(666, $exception['code']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('exception', $data);

        $exception = $data['exception'];
        $this->assertArrayHasKey('class', $exception);
        $this->assertArrayHasKey('message', $exception);
        $this->assertArrayHasKey('file', $exception);
        $this->assertArrayHasKey('line', $exception);
        $this->assertArrayHasKey('code', $exception);

        $this->assertEquals(Exception::class, $exception['class']);
        $this->assertEquals('test', $exception['message']);
        $this->assertEquals(777, $exception['code']);
    }

    public function testNoExceptionCollected(): void
    {
        $collector = new ExceptionCollector(new TimelineCollector());

        $collector->startup();

        $this->assertEquals([], $collector->getCollected());
    }
}
