<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Collector;

use stdClass;
use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Collector\ServiceCollector;
use Yiisoft\Yii\Debug\Collector\TimelineCollector;
use Yiisoft\Yii\Debug\Tests\Shared\AbstractCollectorTestCase;

final class ServiceCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|ServiceCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $time = microtime(true);
        $collector->collect('test', stdClass::class, 'test', [], '', 'success', null, $time, $time + 1);
    }

    protected function getCollector(): CollectorInterface
    {
        return new ServiceCollector(new TimelineCollector());
    }
}
