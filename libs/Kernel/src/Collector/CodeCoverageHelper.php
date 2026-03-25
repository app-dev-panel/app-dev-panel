<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Shared utilities for code coverage processing.
 * Used by both CodeCoverageCollector (Kernel) and CodeCoverageController (API).
 */
final class CodeCoverageHelper
{
    /**
     * Detect which coverage driver is available.
     */
    public static function detectDriver(): ?string
    {
        if (\extension_loaded('pcov') && \ini_get('pcov.enabled')) {
            return 'pcov';
        }

        if (\extension_loaded('xdebug') && \in_array('coverage', \xdebug_info('mode'), true)) {
            return 'xdebug';
        }

        return null;
    }

    /**
     * Process raw coverage data into per-file stats.
     *
     * @param array<string, array<int, int>> $rawCoverage
     * @param string[] $includePaths
     * @param string[] $excludePaths
     * @return array{files: array<string, array{coveredLines: int, executableLines: int, percentage: float}>, coveredLines: int, executableLines: int}
     */
    public static function processCoverage(
        array $rawCoverage,
        array $includePaths = [],
        array $excludePaths = ['vendor'],
    ): array {
        $files = [];
        $totalCovered = 0;
        $totalExecutable = 0;

        foreach ($rawCoverage as $file => $lines) {
            if (!self::shouldIncludeFile($file, $includePaths, $excludePaths)) {
                continue;
            }

            $coveredLines = 0;
            $executableLines = 0;

            foreach ($lines as $status) {
                if ($status === 1) {
                    $coveredLines++;
                    $executableLines++;
                } elseif ($status === -1) {
                    $executableLines++;
                }
            }

            if ($executableLines === 0) {
                continue;
            }

            $files[$file] = [
                'coveredLines' => $coveredLines,
                'executableLines' => $executableLines,
                'percentage' => round(($coveredLines / $executableLines) * 100, 2),
            ];

            $totalCovered += $coveredLines;
            $totalExecutable += $executableLines;
        }

        return [
            'files' => $files,
            'coveredLines' => $totalCovered,
            'executableLines' => $totalExecutable,
        ];
    }

    /**
     * Build the summary array from processed coverage data.
     *
     * @param array<string, array{coveredLines: int, executableLines: int, percentage: float}> $files
     */
    public static function buildSummary(array $files, int $coveredLines, int $executableLines): array
    {
        return [
            'totalFiles' => count($files),
            'coveredLines' => $coveredLines,
            'executableLines' => $executableLines,
            'percentage' => $executableLines > 0 ? round(($coveredLines / $executableLines) * 100, 2) : 0.0,
        ];
    }

    /**
     * Check whether a file should be included in coverage results.
     *
     * @param string[] $includePaths
     * @param string[] $excludePaths
     */
    public static function shouldIncludeFile(string $file, array $includePaths, array $excludePaths): bool
    {
        foreach ($excludePaths as $excludePath) {
            if (
                str_contains($file, DIRECTORY_SEPARATOR . $excludePath . DIRECTORY_SEPARATOR)
                || str_contains($file, '/' . $excludePath . '/')
            ) {
                return false;
            }
        }

        if ($includePaths === []) {
            return true;
        }

        foreach ($includePaths as $includePath) {
            if (str_contains($file, $includePath)) {
                return true;
            }
        }

        return false;
    }
}
