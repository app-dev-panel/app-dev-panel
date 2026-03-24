<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

final class CodeCoverageCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private ?string $driver = null;

    /** @var array<string, array{lines: array<int, int>, coveredLines: int, executableLines: int, percentage: float}> */
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

        $this->driver = $this->detectDriver();
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
                'summary' => [
                    'totalFiles' => 0,
                    'coveredLines' => 0,
                    'executableLines' => 0,
                    'percentage' => 0.0,
                ],
            ];
        }

        return [
            'driver' => $this->driver,
            'files' => $this->files,
            'summary' => [
                'totalFiles' => count($this->files),
                'coveredLines' => $this->coveredLines,
                'executableLines' => $this->executableLines,
                'percentage' => $this->executableLines > 0
                    ? round(($this->coveredLines / $this->executableLines) * 100, 2)
                    : 0.0,
            ],
        ];
    }

    public function getSummary(): array
    {
        if ($this->driver === null) {
            return [];
        }

        return [
            'codeCoverage' => [
                'totalFiles' => count($this->files),
                'coveredLines' => $this->coveredLines,
                'executableLines' => $this->executableLines,
                'percentage' => $this->executableLines > 0
                    ? round(($this->coveredLines / $this->executableLines) * 100, 2)
                    : 0.0,
                'driver' => $this->driver,
            ],
        ];
    }

    private function reset(): void
    {
        $this->driver = null;
        $this->files = [];
        $this->coveredLines = 0;
        $this->executableLines = 0;
    }

    private function detectDriver(): ?string
    {
        if (\extension_loaded('pcov') && \ini_get('pcov.enabled')) {
            return 'pcov';
        }

        if (\extension_loaded('xdebug') && \in_array('coverage', \xdebug_info('mode'), true)) {
            return 'xdebug';
        }

        return null;
    }

    private function startCoverage(): void
    {
        match ($this->driver) {
            'pcov' => $this->startPcov(),
            'xdebug' => $this->startXdebug(),
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

        $this->processCoverage($rawCoverage);
        $this->timelineCollector->collect($this, count($this->files) . ' files');
    }

    private function startPcov(): void
    {
        \pcov\start();
    }

    /**
     * @return array<string, array<int, int>>
     */
    private function stopPcov(): array
    {
        \pcov\stop();

        $includes = $this->includePaths !== [] ? $this->includePaths : ['.'];
        $excludes = $this->excludePaths;

        /** @var array<string, array<int, int>> */
        return \pcov\collect(\pcov\inclusive, ...$includes !== [] ? [$includes[0]] : ['.']);
    }

    private function startXdebug(): void
    {
        \xdebug_start_code_coverage(\XDEBUG_CC_UNUSED | \XDEBUG_CC_DEAD_CODE);
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

    /**
     * @param array<string, array<int, int>> $rawCoverage
     */
    private function processCoverage(array $rawCoverage): void
    {
        $this->files = [];
        $this->coveredLines = 0;
        $this->executableLines = 0;

        foreach ($rawCoverage as $file => $lines) {
            if (!$this->shouldIncludeFile($file)) {
                continue;
            }

            $coveredLines = 0;
            $executableLines = 0;

            foreach ($lines as $line => $status) {
                // Status: 1 = executed, -1 = not executed, -2 = dead code (xdebug)
                if ($status === 1) {
                    $coveredLines++;
                    $executableLines++;
                } elseif ($status === -1) {
                    $executableLines++;
                }

                // -2 (dead code) and 0 (not executable) are skipped
            }

            if ($executableLines === 0) {
                continue;
            }

            $this->files[$file] = [
                'lines' => $lines,
                'coveredLines' => $coveredLines,
                'executableLines' => $executableLines,
                'percentage' => round(($coveredLines / $executableLines) * 100, 2),
            ];

            $this->coveredLines += $coveredLines;
            $this->executableLines += $executableLines;
        }
    }

    private function shouldIncludeFile(string $file): bool
    {
        foreach ($this->excludePaths as $excludePath) {
            if (
                str_contains($file, DIRECTORY_SEPARATOR . $excludePath . DIRECTORY_SEPARATOR)
                || str_contains($file, '/' . $excludePath . '/')
            ) {
                return false;
            }
        }

        if ($this->includePaths === []) {
            return true;
        }

        foreach ($this->includePaths as $includePath) {
            if (str_contains($file, $includePath)) {
                return true;
            }
        }

        return false;
    }
}
