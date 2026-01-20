<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Shared;

use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Collector\SummaryCollectorInterface;

abstract class AbstractCollectorTestCase extends TestCase
{
    public function testCollect(): void
    {
        $summaryData = null;
        $collector = $this->getCollector();

        $collector->startup();
        $this->collectTestData($collector);
        $data = $collector->getCollected();
        if ($collector instanceof SummaryCollectorInterface) {
            $summaryData = $collector->getSummary();
        }
        $collector->shutdown();

        $this->assertSame($collector::class, $collector->getName());
        $this->checkCollectedData($data);
        if ($collector instanceof SummaryCollectorInterface) {
            $this->checkSummaryData($summaryData);
        }
    }

    public function testEmptyCollector(): void
    {
        $collector = $this->getCollector();

        $this->assertEquals([], $collector->getCollected());
        if ($collector instanceof SummaryCollectorInterface) {
            $this->assertEquals([], $collector->getSummary());
        }
    }

    public function testInactiveCollector(): void
    {
        $collector = $this->getCollector();

        $this->collectTestData($collector);

        $this->assertEquals([], $collector->getCollected());
        if ($collector instanceof SummaryCollectorInterface) {
            $this->assertEquals([], $collector->getSummary());
        }
    }

    abstract protected function getCollector(): CollectorInterface;

    abstract protected function collectTestData(CollectorInterface $collector): void;

    protected function checkCollectedData(array $data): void
    {
        $this->assertNotEmpty($data);
    }

    protected function checkSummaryData(array $data): void
    {
        $this->assertNotEmpty($data);
    }
}
