<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Collector;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Yiisoft\Yii\Console\Event\ApplicationShutdown;
use Yiisoft\Yii\Console\Event\ApplicationStartup;
use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Collector\Console\ConsoleAppInfoCollector;
use Yiisoft\Yii\Debug\Collector\TimelineCollector;
use Yiisoft\Yii\Debug\Tests\Shared\AbstractCollectorTestCase;

use function sleep;
use function usleep;

final class ConsoleAppInfoCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|ConsoleAppInfoCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->collect(new ApplicationStartup(null));

        $command = $this->createMock(Command::class);
        $input = new ArrayInput([]);
        $output = new NullOutput();
        $collector->collect(new ConsoleCommandEvent(null, $input, $output));
        $collector->collect(new ConsoleErrorEvent($input, $output, new Exception()));
        $collector->collect(new ConsoleTerminateEvent($command, $input, $output, 2));

        DIRECTORY_SEPARATOR === '\\' ? sleep(1) : usleep(123_000);

        $collector->collect(new ApplicationShutdown(0));
    }

    protected function getCollector(): CollectorInterface
    {
        return new ConsoleAppInfoCollector(new TimelineCollector());
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertGreaterThan(0.122, $data['applicationProcessingTime']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('console', $data);
        $this->assertArrayHasKey('php', $data['console']);
        $this->assertArrayHasKey('version', $data['console']['php']);
        $this->assertArrayHasKey('request', $data['console']);
        $this->assertArrayHasKey('startTime', $data['console']['request']);
        $this->assertArrayHasKey('processingTime', $data['console']['request']);
        $this->assertArrayHasKey('memory', $data['console']);
        $this->assertArrayHasKey('peakUsage', $data['console']['memory']);

        $this->assertEquals(PHP_VERSION, $data['console']['php']['version']);
    }
}
