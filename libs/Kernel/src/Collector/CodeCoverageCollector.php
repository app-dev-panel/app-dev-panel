<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

final class CodeCoverageCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private ?string $driver = null;

    /** @var array<string, array{coveredLines: int, executableLines: int, percentage: float}> */
    private array $files = [];

    private int $coveredLines = 0;
    private int $executableLines = 0;

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
        private readonly array $includePaths = [],
        private readonly array $excludePaths = ['vendor'],
    ) {}

    public function startup(): void
    {
        $this->isActive = true;

        $this->driver = CodeCoverageHelper::detectDriver();
        if ($this->driver === null) {
            return;
        }

        $this->startCoverage();
    }

    public function shutdown(): void
    {
        if ($this->driver !== null) {
            $this->stopCoverage();
        }

        $this->isActive = false;
    }

    public function getCollected(): array
    {
        if ($this->driver === null) {
            return [
                'driver' => null,
                'error' => 'No code coverage driver available (install pcov or xdebug)',
                'files' => [],
                'summary' => CodeCoverageHelper::buildSummary([], 0, 0),
            ];
        }

        return [
            'driver' => $this->driver,
            'files' => $this->files,
            'summary' => CodeCoverageHelper::buildSummary($this->files, $this->coveredLines, $this->executableLines),
        ];
    }

    public function getSummary(): array
    {
        if ($this->driver === null) {
            return [];
        }

        $summary = CodeCoverageHelper::buildSummary($this->files, $this->coveredLines, $this->executableLines);
        $summary['driver'] = $this->driver;

        return ['codeCoverage' => $summary];
    }

    private function reset(): void
    {
        $this->driver = null;
        $this->files = [];
        $this->coveredLines = 0;
        $this->executableLines = 0;
    }

    private function startCoverage(): void
    {
        match ($this->driver) {
            'pcov' => \pcov\start(),
            'xdebug' => \xdebug_start_code_coverage(\XDEBUG_CC_UNUSED | \XDEBUG_CC_DEAD_CODE),
            default => null,
        };
    }

    private function stopCoverage(): void
    {
        $rawCoverage = match ($this->driver) {
            'pcov' => $this->stopPcov(),
            'xdebug' => $this->stopXdebug(),
            default => [],
        };

        $result = CodeCoverageHelper::processCoverage($rawCoverage, $this->includePaths, $this->excludePaths);
        $this->files = $result['files'];
        $this->coveredLines = $result['coveredLines'];
        $this->executableLines = $result['executableLines'];
        $this->timelineCollector->collect($this, count($this->files) . ' files');
    }

    /**
     * @return array<string, array<int, int>>
     */
    private function stopPcov(): array
    {
        \pcov\stop();

        $filter = $this->includePaths !== [] ? $this->includePaths : ['.'];

        /** @var array<string, array<int, int>> */
        return \pcov\collect(\pcov\inclusive, $filter);
    }

    /**
     * @return array<string, array<int, int>>
     */
    private function stopXdebug(): array
    {
        /** @var array<string, array<int, int>> $coverage */
        $coverage = \xdebug_get_code_coverage();
        \xdebug_stop_code_coverage();

        return $coverage;
    }
}
