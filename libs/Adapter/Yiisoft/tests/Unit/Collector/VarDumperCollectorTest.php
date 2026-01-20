<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Collector;

use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Collector\TimelineCollector;
use Yiisoft\Yii\Debug\Collector\VarDumperCollector;
use Yiisoft\Yii\Debug\Tests\Shared\AbstractCollectorTestCase;

final class VarDumperCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|VarDumperCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->collect('test', 'file:123');
    }

    protected function getCollector(): CollectorInterface
    {
        return new VarDumperCollector(new TimelineCollector());
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);
        $this->assertCount(1, $data);
        $this->assertCount(2, $data[0]);
        $this->assertSame('test', $data[0]['variable']);
        $this->assertSame('file:123', $data[0]['line']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('var-dumper', $data);
        $this->assertArrayHasKey('total', $data['var-dumper']);
        $this->assertEquals(1, $data['var-dumper']['total']);
    }
}
