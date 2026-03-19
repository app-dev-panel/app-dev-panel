<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

use function sleep;
use function usleep;

final class ConsoleAppInfoCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|ConsoleAppInfoCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->markApplicationStarted();

        $command = $this->createMock(Command::class);
        $input = new ArrayInput([]);
        $output = new NullOutput();
        $collector->collect(new ConsoleCommandEvent(null, $input, $output));
        $collector->collect(new ConsoleErrorEvent($input, $output, new Exception()));
        $collector->collect(new ConsoleTerminateEvent($command, $input, $output, 2));

        DIRECTORY_SEPARATOR === '\\' ? sleep(1) : usleep(123_000);

        $collector->markApplicationFinished();
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

    public function testAdapterName(): void
    {
        $collector = new ConsoleAppInfoCollector(new TimelineCollector(), 'Yii3');
        $collector->startup();
        $collector->markApplicationStarted();

        $input = new ArrayInput([]);
        $output = new NullOutput();
        $collector->collect(new ConsoleCommandEvent(null, $input, $output));
        $collector->collect(new ConsoleTerminateEvent($this->createMock(Command::class), $input, $output, 0));

        $collector->markApplicationFinished();

        $collected = $collector->getCollected();
        $this->assertSame('Yii3', $collected['adapter']);

        $summary = $collector->getSummary();
        $this->assertIsArray($summary['console']);
        $this->assertSame('Yii3', $summary['console']['adapter']);
    }

    public function testAdapterNameDefaultsToEmpty(): void
    {
        $collector = new ConsoleAppInfoCollector(new TimelineCollector());
        $collector->startup();
        $collector->markApplicationStarted();

        $input = new ArrayInput([]);
        $output = new NullOutput();
        $collector->collect(new ConsoleCommandEvent(null, $input, $output));
        $collector->collect(new ConsoleTerminateEvent($this->createMock(Command::class), $input, $output, 0));

        $collector->markApplicationFinished();

        $collected = $collector->getCollected();
        $this->assertSame('', $collected['adapter']);

        $summary = $collector->getSummary();
        $this->assertIsArray($summary['console']);
        $this->assertSame('', $summary['console']['adapter']);
    }
}
