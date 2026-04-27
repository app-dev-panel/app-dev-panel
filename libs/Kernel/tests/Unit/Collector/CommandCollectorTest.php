<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class CommandCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|CommandCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->collect(
            new ConsoleCommandEvent(new Command('test'), new StringInput('test'), new BufferedOutput()),
        );
        $collector->collect(new ConsoleErrorEvent(new StringInput('test1'), new BufferedOutput(), new Exception()));
        $collector->collect(
            new ConsoleTerminateEvent(new Command('test1'), new StringInput('test1'), new BufferedOutput(), 0),
        );
    }

    public function testCollectWithInactiveCollector(): void
    {
        $collector = $this->getCollector();
        $baselineCollected = $collector->getCollected();
        $baselineSummary = method_exists($collector, 'getSummary') ? $collector->getSummary() : null;
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

    public function testCollectCommandDataStoresData(): void
    {
        $collector = $this->getCollector();
        $collector->startup();

        $collector->collectCommandData([
            'name' => 'migrate/up',
            'input' => 'migrate/up --interactive=0',
        ]);

        $collected = $collector->getCollected();
        $this->assertArrayHasKey('command', $collected);
        $this->assertSame('migrate/up', $collected['command']['name']);
        $this->assertSame('migrate/up --interactive=0', $collected['command']['input']);
        $this->assertSame(-1, $collected['command']['exitCode']);
    }

    public function testCollectCommandDataWithExitCode(): void
    {
        $collector = $this->getCollector();
        $collector->startup();

        $collector->collectCommandData([
            'name' => 'cache/clear',
            'input' => 'cache/clear',
            'exitCode' => 0,
        ]);

        $collected = $collector->getCollected();
        $this->assertSame(0, $collected['command']['exitCode']);
    }

    public function testCollectCommandDataWithError(): void
    {
        $collector = $this->getCollector();
        $collector->startup();

        $collector->collectCommandData([
            'name' => 'migrate/up',
            'input' => 'migrate/up',
            'exitCode' => 1,
            'error' => 'Migration failed',
        ]);

        $collected = $collector->getCollected();
        $this->assertSame('Migration failed', $collected['command']['error']);
        $this->assertSame(1, $collected['command']['exitCode']);
    }

    public function testCollectCommandDataOverwritesPrevious(): void
    {
        $collector = $this->getCollector();
        $collector->startup();

        $collector->collectCommandData([
            'name' => 'migrate/up',
            'input' => 'migrate/up',
        ]);

        $collector->collectCommandData([
            'name' => 'migrate/up',
            'input' => 'migrate/up',
            'exitCode' => 0,
        ]);

        $collected = $collector->getCollected();
        $this->assertSame(0, $collected['command']['exitCode']);
    }

    public function testCollectCommandDataSummary(): void
    {
        $collector = $this->getCollector();
        $collector->startup();

        $collector->collectCommandData([
            'name' => 'cache/flush',
            'input' => 'cache/flush --all',
            'exitCode' => 0,
        ]);

        $summary = $collector->getSummary();
        $this->assertArrayHasKey('command', $summary);
        $this->assertSame('cache/flush', $summary['command']['name']);
        $this->assertSame('cache/flush --all', $summary['command']['input']);
        $this->assertSame(0, $summary['command']['exitCode']);
        $this->assertNull($summary['command']['class']);
    }

    public function testGetSummaryWithNoCommandEvents(): void
    {
        $collector = $this->getCollector();
        $collector->startup();

        // No events collected, so getSummary should return empty
        $summary = $collector->getSummary();
        $this->assertSame([], $summary);
    }

    public function testCollectCommandDataInactiveDoesNothing(): void
    {
        $collector = $this->getCollector();
        // Don't call startup() — collector is inactive

        $collector->collectCommandData([
            'name' => 'test',
            'input' => 'test',
        ]);

        $this->assertEmpty($collector->getCollected());
    }
}
