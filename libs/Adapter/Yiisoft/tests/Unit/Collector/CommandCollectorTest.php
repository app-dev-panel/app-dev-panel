<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Collector;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\StringInput;
use Yiisoft\Yii\Console\Output\ConsoleBufferedOutput;
use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Collector\Console\CommandCollector;
use Yiisoft\Yii\Debug\Collector\TimelineCollector;
use Yiisoft\Yii\Debug\Tests\Shared\AbstractCollectorTestCase;

final class CommandCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|CommandCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->collect(
            new ConsoleCommandEvent(
                new Command('test'),
                new StringInput('test'),
                new ConsoleBufferedOutput()
            )
        );
        $collector->collect(
            new ConsoleErrorEvent(
                new StringInput('test1'),
                new ConsoleBufferedOutput(),
                new Exception()
            )
        );
        $collector->collect(
            new ConsoleTerminateEvent(
                new Command('test1'),
                new StringInput('test1'),
                new ConsoleBufferedOutput(),
                0
            )
        );
    }

    public function testCollectWithInactiveCollector(): void
    {
        $collector = $this->getCollector();
        $this->collectTestData($collector);

        $collected = $collector->getCollected();
        $this->assertEmpty($collected);
    }

    protected function getCollector(): CollectorInterface
    {
        return new CommandCollector(new TimelineCollector());
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);
        $this->assertCount(3, $data);
        $this->assertEquals('test', $data[ConsoleCommandEvent::class]['input']);
        $this->assertEmpty($data[ConsoleCommandEvent::class]['output']);
    }

    protected function checkSummaryData(array $data): void
    {
        $this->assertArrayHasKey('command', $data);
        $this->assertArrayHasKey('input', $data['command']);
        $this->assertArrayHasKey('class', $data['command']);
        $this->assertEquals('test1', $data['command']['input']);
        $this->assertEquals(null, $data['command']['class']);
    }
}
