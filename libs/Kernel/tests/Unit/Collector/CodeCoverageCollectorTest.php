<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CodeCoverageCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class CodeCoverageCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new CodeCoverageCollector(new TimelineCollector(), includePaths: [], excludePaths: ['vendor']);
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        // Coverage is collected automatically between startup() and shutdown()
        // Execute some code to generate coverage data
        $result = array_map(fn(int $i) => $i * 2, range(1, 10));
        $this->assertCount(10, $result);
    }

    public function testCollect(): void
    {
        $collector = $this->getCollector();

        $collector->startup();
        $this->collectTestData($collector);
        $data = $collector->getCollected();
        $summaryData = $collector->getSummary();
        $collector->shutdown();

        $this->assertSame($collector::class, $collector->getId());
        $this->assertSame('Code Coverage', $collector->getName());

        if ($data['driver'] === null) {
            // No coverage driver available — verify graceful fallback
            $this->assertSame('No code coverage driver available (install pcov or xdebug)', $data['error']);
            $this->assertSame([], $data['files']);
            $this->assertSame(0, $data['summary']['totalFiles']);
            $this->assertSame([], $summaryData);
        } else {
            $this->checkCollectedData($data);
            $this->checkSummaryData($summaryData);
        }
    }

    public function testEmptyCollector(): void
    {
        $collector = $this->getCollector();

        $data = $collector->getCollected();
        $this->assertSame(null, $data['driver']);
    }

    public function testInactiveCollector(): void
    {
        $collector = $this->getCollector();

        $this->collectTestData($collector);

        $data = $collector->getCollected();
        $this->assertSame(null, $data['driver']);
    }

    public function testDriverDetection(): void
    {
        $collector = $this->getCollector();

        $collector->startup();
        $data = $collector->getCollected();
        $collector->shutdown();

        $this->assertContains($data['driver'], [null, 'pcov', 'xdebug']);
    }

    public function testCollectedDataStructure(): void
    {
        $collector = $this->getCollector();

        $collector->startup();
        $this->collectTestData($collector);
        $data = $collector->getCollected();
        $collector->shutdown();

        $this->assertArrayHasKey('driver', $data);
        $this->assertArrayHasKey('files', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('totalFiles', $data['summary']);
        $this->assertArrayHasKey('coveredLines', $data['summary']);
        $this->assertArrayHasKey('executableLines', $data['summary']);
        $this->assertArrayHasKey('percentage', $data['summary']);
    }

    public function testExcludePaths(): void
    {
        $collector = new CodeCoverageCollector(
            new TimelineCollector(),
            includePaths: [],
            excludePaths: ['vendor', 'tests'],
        );

        $collector->startup();
        $this->collectTestData($collector);
        $data = $collector->getCollected();
        $collector->shutdown();

        if ($data['driver'] !== null) {
            foreach (array_keys($data['files']) as $file) {
                $this->assertStringNotContainsString('/vendor/', $file);
                $this->assertStringNotContainsString('/tests/', $file);
            }
        }
    }

    public function testSummaryStructure(): void
    {
        $collector = $this->getCollector();

        $collector->startup();
        $this->collectTestData($collector);
        $summaryData = $collector->getSummary();
        $collector->shutdown();

        if ($summaryData !== []) {
            $this->assertArrayHasKey('codeCoverage', $summaryData);
            $this->assertArrayHasKey('totalFiles', $summaryData['codeCoverage']);
            $this->assertArrayHasKey('coveredLines', $summaryData['codeCoverage']);
            $this->assertArrayHasKey('executableLines', $summaryData['codeCoverage']);
            $this->assertArrayHasKey('percentage', $summaryData['codeCoverage']);
            $this->assertArrayHasKey('driver', $summaryData['codeCoverage']);
        }
    }

    protected function checkCollectedData(array $data): void
    {
        $this->assertNotEmpty($data);
        $this->assertNotNull($data['driver']);
        $this->assertIsArray($data['files']);
        $this->assertIsArray($data['summary']);
        $this->assertIsInt($data['summary']['totalFiles']);
        $this->assertIsInt($data['summary']['coveredLines']);
        $this->assertIsInt($data['summary']['executableLines']);
        $this->assertIsFloat($data['summary']['percentage']);
    }

    protected function checkSummaryData(array $data): void
    {
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('codeCoverage', $data);
        $this->assertIsInt($data['codeCoverage']['totalFiles']);
        $this->assertIsFloat($data['codeCoverage']['percentage']);
        $this->assertNotNull($data['codeCoverage']['driver']);
    }
}
