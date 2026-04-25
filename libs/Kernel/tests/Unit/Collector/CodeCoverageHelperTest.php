<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CodeCoverageHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CodeCoverageHelperTest extends TestCase
{
    public function testDetectDriverReturnsNullOrKnownDriver(): void
    {
        $driver = CodeCoverageHelper::detectDriver();
        $this->assertContains($driver, [null, 'pcov', 'xdebug']);
    }

    public function testProcessCoverageEmpty(): void
    {
        $result = CodeCoverageHelper::processCoverage([]);

        $this->assertSame([], $result['files']);
        $this->assertSame(0, $result['coveredLines']);
        $this->assertSame(0, $result['executableLines']);
    }

    public function testProcessCoverageWithData(): void
    {
        $rawCoverage = [
            '/app/src/Foo.php' => [
                10 => 1, // executed
                11 => 1, // executed
                12 => -1, // not executed
                13 => -1, // not executed
                14 => -2, // dead code (skipped)
            ],
            '/app/src/Bar.php' => [
                5 => 1,
                6 => 1,
                7 => 1,
            ],
        ];

        $result = CodeCoverageHelper::processCoverage($rawCoverage);

        $this->assertCount(2, $result['files']);
        $this->assertSame(5, $result['coveredLines']);
        $this->assertSame(7, $result['executableLines']);

        // Foo: 2 covered, 4 executable, 50%
        $foo = $result['files']['/app/src/Foo.php'];
        $this->assertSame(2, $foo['coveredLines']);
        $this->assertSame(4, $foo['executableLines']);
        $this->assertSame(50.0, $foo['percentage']);

        // Bar: 3 covered, 3 executable, 100%
        $bar = $result['files']['/app/src/Bar.php'];
        $this->assertSame(3, $bar['coveredLines']);
        $this->assertSame(3, $bar['executableLines']);
        $this->assertSame(100.0, $bar['percentage']);
    }

    public function testProcessCoverageExcludesPaths(): void
    {
        $rawCoverage = [
            '/app/vendor/lib/Foo.php' => [10 => 1],
            '/app/src/Bar.php' => [5 => 1],
        ];

        $result = CodeCoverageHelper::processCoverage($rawCoverage, excludePaths: ['vendor']);

        $this->assertCount(1, $result['files']);
        $this->assertArrayHasKey('/app/src/Bar.php', $result['files']);
        $this->assertArrayNotHasKey('/app/vendor/lib/Foo.php', $result['files']);
    }

    public function testProcessCoverageIncludePaths(): void
    {
        $rawCoverage = [
            '/app/src/Foo.php' => [10 => 1],
            '/app/other/Bar.php' => [5 => 1],
        ];

        $result = CodeCoverageHelper::processCoverage($rawCoverage, includePaths: ['/app/src'], excludePaths: []);

        $this->assertCount(1, $result['files']);
        $this->assertArrayHasKey('/app/src/Foo.php', $result['files']);
    }

    public function testProcessCoverageSkipsFilesWithNoExecutableLines(): void
    {
        $rawCoverage = [
            '/app/src/Empty.php' => [
                10 => -2, // dead code only
                11 => -2,
            ],
        ];

        $result = CodeCoverageHelper::processCoverage($rawCoverage, excludePaths: []);

        $this->assertSame([], $result['files']);
    }

    public function testBuildSummaryEmpty(): void
    {
        $summary = CodeCoverageHelper::buildSummary([], 0, 0);

        $this->assertSame(0, $summary['totalFiles']);
        $this->assertSame(0, $summary['coveredLines']);
        $this->assertSame(0, $summary['executableLines']);
        $this->assertSame(0.0, $summary['percentage']);
    }

    public function testBuildSummaryWithData(): void
    {
        $files = [
            '/app/src/Foo.php' => ['coveredLines' => 8, 'executableLines' => 10, 'percentage' => 80.0],
            '/app/src/Bar.php' => ['coveredLines' => 3, 'executableLines' => 3, 'percentage' => 100.0],
        ];

        $summary = CodeCoverageHelper::buildSummary($files, 11, 13);

        $this->assertSame(2, $summary['totalFiles']);
        $this->assertSame(11, $summary['coveredLines']);
        $this->assertSame(13, $summary['executableLines']);
        $this->assertSame(84.62, $summary['percentage']);
    }

    #[DataProvider('shouldIncludeFileProvider')]
    public function testShouldIncludeFile(bool $expected, string $file, array $includePaths, array $excludePaths): void
    {
        $this->assertSame($expected, CodeCoverageHelper::shouldIncludeFile($file, $includePaths, $excludePaths));
    }

    public static function shouldIncludeFileProvider(): iterable
    {
        yield 'no filters — include all' => [true, '/app/src/Foo.php', [], []];
        yield 'exclude vendor' => [false, '/app/vendor/lib/Foo.php', [], ['vendor']];
        yield 'include src only — match' => [true, '/app/src/Foo.php', ['/app/src'], []];
        yield 'include src only — no match' => [false, '/app/other/Bar.php', ['/app/src'], []];
        yield 'exclude + include combined' => [false, '/app/src/vendor/Foo.php', ['/app/src'], ['vendor']];
        yield 'exclude takes precedence' => [false, '/app/vendor/src/Foo.php', ['/app'], ['vendor']];
    }

    public function testProcessCoverageWithMultipleExcludePaths(): void
    {
        $rawCoverage = [
            '/app/vendor/lib/A.php' => [1 => 1],
            '/app/cache/gen/B.php' => [1 => 1],
            '/app/src/C.php' => [1 => 1],
        ];

        $result = CodeCoverageHelper::processCoverage($rawCoverage, excludePaths: ['vendor', 'cache']);

        $this->assertCount(1, $result['files']);
        $this->assertArrayHasKey('/app/src/C.php', $result['files']);
    }

    public function testProcessCoverageWithMultipleIncludePaths(): void
    {
        $rawCoverage = [
            '/app/src/A.php' => [1 => 1],
            '/app/lib/B.php' => [1 => 1],
            '/app/other/C.php' => [1 => 1],
        ];

        $result = CodeCoverageHelper::processCoverage(
            $rawCoverage,
            includePaths: ['/app/src', '/app/lib'],
            excludePaths: [],
        );

        $this->assertCount(2, $result['files']);
        $this->assertArrayHasKey('/app/src/A.php', $result['files']);
        $this->assertArrayHasKey('/app/lib/B.php', $result['files']);
    }

    public function testProcessCoverageCalculatesPercentageCorrectly(): void
    {
        $rawCoverage = [
            '/app/src/File.php' => [
                1 => 1, // covered
                2 => 1, // covered
                3 => -1, // executable but not covered
            ],
        ];

        $result = CodeCoverageHelper::processCoverage($rawCoverage, excludePaths: []);

        $file = $result['files']['/app/src/File.php'];
        $this->assertSame(66.67, $file['percentage']);
    }

    public function testBuildSummaryPercentageWithZeroExecutable(): void
    {
        $summary = CodeCoverageHelper::buildSummary([], 0, 0);

        $this->assertSame(0.0, $summary['percentage']);
    }

    public function testShouldIncludeFileWithForwardSlashSeparators(): void
    {
        // Test the forward-slash branch of exclude check
        $this->assertFalse(CodeCoverageHelper::shouldIncludeFile('/app/vendor/lib/X.php', [], ['vendor']));
    }

    public function testProcessCoverageAllUncoveredLines(): void
    {
        $rawCoverage = [
            '/app/src/Uncovered.php' => [
                1 => -1,
                2 => -1,
                3 => -1,
            ],
        ];

        $result = CodeCoverageHelper::processCoverage($rawCoverage, excludePaths: []);

        $this->assertSame(0, $result['coveredLines']);
        $this->assertSame(3, $result['executableLines']);
        $this->assertSame(0.0, $result['files']['/app/src/Uncovered.php']['percentage']);
    }

    public function testProcessCoverageDefaultExcludesVendor(): void
    {
        $rawCoverage = [
            '/app/vendor/package/File.php' => [1 => 1, 2 => 1],
            '/app/src/MyClass.php' => [1 => 1],
        ];

        // Default excludePaths is ['vendor']
        $result = CodeCoverageHelper::processCoverage($rawCoverage);

        $this->assertCount(1, $result['files']);
        $this->assertArrayHasKey('/app/src/MyClass.php', $result['files']);
        $this->assertArrayNotHasKey('/app/vendor/package/File.php', $result['files']);
    }

    public function testShouldIncludeFileWithBackslashSeparators(): void
    {
        // On Windows, DIRECTORY_SEPARATOR is \, test the DIRECTORY_SEPARATOR branch
        $file = '/app' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'X.php';
        $this->assertFalse(CodeCoverageHelper::shouldIncludeFile($file, [], ['vendor']));
    }

    public function testProcessCoverageMixedStatusCodes(): void
    {
        $rawCoverage = [
            '/app/src/Mixed.php' => [
                1 => 1, // covered
                2 => -1, // uncovered
                3 => -2, // dead code (ignored)
                4 => 0, // not counted (neither covered nor executable)
                5 => 1, // covered
            ],
        ];

        $result = CodeCoverageHelper::processCoverage($rawCoverage, excludePaths: []);

        $file = $result['files']['/app/src/Mixed.php'];
        // status=1 counted as covered+executable (lines 1,5)
        // status=-1 counted as executable only (line 2)
        // status=-2 and 0 are ignored
        $this->assertSame(2, $file['coveredLines']);
        $this->assertSame(3, $file['executableLines']);
        $this->assertSame(66.67, $file['percentage']);
    }

    public function testBuildSummaryWithSingleFile(): void
    {
        $files = [
            '/app/src/Only.php' => ['coveredLines' => 5, 'executableLines' => 10, 'percentage' => 50.0],
        ];

        $summary = CodeCoverageHelper::buildSummary($files, 5, 10);

        $this->assertSame(1, $summary['totalFiles']);
        $this->assertSame(5, $summary['coveredLines']);
        $this->assertSame(10, $summary['executableLines']);
        $this->assertSame(50.0, $summary['percentage']);
    }

    public function testShouldIncludeFileMultipleIncludePathsNoMatch(): void
    {
        $this->assertFalse(CodeCoverageHelper::shouldIncludeFile('/app/tests/Unit.php', ['/app/src', '/app/lib'], []));
    }

    public function testProcessCoverageIncludeAndExcludeTogether(): void
    {
        $rawCoverage = [
            '/app/src/Good.php' => [1 => 1],
            '/app/src/vendor/Bad.php' => [1 => 1],
            '/app/lib/Other.php' => [1 => 1],
        ];

        $result = CodeCoverageHelper::processCoverage(
            $rawCoverage,
            includePaths: ['/app/src'],
            excludePaths: ['vendor'],
        );

        $this->assertCount(1, $result['files']);
        $this->assertArrayHasKey('/app/src/Good.php', $result['files']);
    }

    public function testBuildSummaryPercentageRoundsCorrectly(): void
    {
        $files = [
            '/a.php' => ['coveredLines' => 1, 'executableLines' => 3, 'percentage' => 33.33],
        ];

        $summary = CodeCoverageHelper::buildSummary($files, 1, 3);

        $this->assertSame(33.33, $summary['percentage']);
    }

    public function testDetectDriverReturnsStringOrNull(): void
    {
        $result = CodeCoverageHelper::detectDriver();

        // The result must be one of the known drivers or null
        $this->assertContains($result, [null, 'pcov', 'xdebug']);
    }

    public function testDetectDriverPrefersExistingDriver(): void
    {
        $result = CodeCoverageHelper::detectDriver();

        // If any driver extension is loaded and active, result should not be null
        if (\extension_loaded('pcov') && \ini_get('pcov.enabled')) {
            $this->assertSame('pcov', $result);
        } elseif (\extension_loaded('xdebug')) {
            $this->assertContains($result, [null, 'xdebug']);
        } else {
            $this->assertNull($result);
        }
    }

    public function testProcessCoverageWithEmptyLinesArray(): void
    {
        $rawCoverage = [
            '/app/src/Empty.php' => [],
        ];

        $result = CodeCoverageHelper::processCoverage($rawCoverage, excludePaths: []);

        // File with no lines should be skipped (0 executable lines)
        $this->assertSame([], $result['files']);
        $this->assertSame(0, $result['coveredLines']);
        $this->assertSame(0, $result['executableLines']);
    }

    public function testProcessCoverageSingleCoveredLine(): void
    {
        $rawCoverage = [
            '/app/src/One.php' => [
                1 => 1,
            ],
        ];

        $result = CodeCoverageHelper::processCoverage($rawCoverage, excludePaths: []);

        $file = $result['files']['/app/src/One.php'];
        $this->assertSame(1, $file['coveredLines']);
        $this->assertSame(1, $file['executableLines']);
        $this->assertSame(100.0, $file['percentage']);
    }

    public function testProcessCoverageSingleUncoveredLine(): void
    {
        $rawCoverage = [
            '/app/src/One.php' => [
                1 => -1,
            ],
        ];

        $result = CodeCoverageHelper::processCoverage($rawCoverage, excludePaths: []);

        $file = $result['files']['/app/src/One.php'];
        $this->assertSame(0, $file['coveredLines']);
        $this->assertSame(1, $file['executableLines']);
        $this->assertSame(0.0, $file['percentage']);
    }

    public function testBuildSummaryWithManyFiles(): void
    {
        $files = [];
        $totalCovered = 0;
        $totalExecutable = 0;
        for ($i = 0; $i < 100; $i++) {
            $covered = $i;
            $executable = 100;
            $files["/app/src/File{$i}.php"] = [
                'coveredLines' => $covered,
                'executableLines' => $executable,
                'percentage' => (float) $covered,
            ];
            $totalCovered += $covered;
            $totalExecutable += $executable;
        }

        $summary = CodeCoverageHelper::buildSummary($files, $totalCovered, $totalExecutable);

        $this->assertSame(100, $summary['totalFiles']);
        $this->assertSame($totalCovered, $summary['coveredLines']);
        $this->assertSame($totalExecutable, $summary['executableLines']);
        $this->assertSame(round(($totalCovered / $totalExecutable) * 100, 2), $summary['percentage']);
    }

    public function testShouldIncludeFileWithMultipleExcludePathsPartialMatch(): void
    {
        // File path contains 'vendor' as substring but not as a directory segment
        $this->assertTrue(CodeCoverageHelper::shouldIncludeFile('/app/src/VendorHelper.php', [], ['vendor']));
    }

    public function testDetectDriverReturnsNullWhenNoDriverAvailable(): void
    {
        // If neither pcov nor xdebug is loaded/enabled, detectDriver returns null
        // This test exercises the full method regardless of environment
        $result = CodeCoverageHelper::detectDriver();

        if (\extension_loaded('pcov') && \ini_get('pcov.enabled')) {
            $this->assertSame('pcov', $result);
        } elseif (\extension_loaded('xdebug') && \in_array('coverage', \xdebug_info('mode'), true)) {
            $this->assertSame('xdebug', $result);
        } else {
            $this->assertNull($result);
        }
    }

    public function testProcessCoverageWithLargeDataset(): void
    {
        $rawCoverage = [];
        for ($i = 0; $i < 50; $i++) {
            $lines = [];
            for ($j = 1; $j <= 20; $j++) {
                $lines[$j] = ($j % 3) === 0 ? -1 : 1;
            }
            $rawCoverage["/app/src/File{$i}.php"] = $lines;
        }

        $result = CodeCoverageHelper::processCoverage($rawCoverage, excludePaths: []);

        $this->assertCount(50, $result['files']);
        // Each file: 14 covered (status=1), 6 uncovered (status=-1), 20 executable
        $this->assertSame(700, $result['coveredLines']);
        $this->assertSame(1000, $result['executableLines']);
    }

    public function testShouldIncludeFileEmptyExcludeAndInclude(): void
    {
        // Both empty - should include
        $this->assertTrue(CodeCoverageHelper::shouldIncludeFile('/any/path/file.php', [], []));
    }

    public function testShouldIncludeFileMultipleExcludeFirstMatch(): void
    {
        // First exclude pattern matches
        $this->assertFalse(CodeCoverageHelper::shouldIncludeFile(
            '/app/vendor/lib/X.php',
            [],
            ['vendor', 'cache', 'tmp'],
        ));
    }

    public function testShouldIncludeFileMultipleExcludeSecondMatch(): void
    {
        // Second exclude pattern matches
        $this->assertFalse(CodeCoverageHelper::shouldIncludeFile(
            '/app/cache/gen/X.php',
            [],
            ['vendor', 'cache', 'tmp'],
        ));
    }

    public function testShouldIncludeFileIncludePathFirstMatch(): void
    {
        // First include path matches
        $this->assertTrue(CodeCoverageHelper::shouldIncludeFile('/app/src/Foo.php', ['/app/src', '/app/lib'], []));
    }

    public function testShouldIncludeFileIncludePathSecondMatch(): void
    {
        // Second include path matches
        $this->assertTrue(CodeCoverageHelper::shouldIncludeFile('/app/lib/Bar.php', ['/app/src', '/app/lib'], []));
    }

    public function testShouldIncludeFileIncludePathNoneMatch(): void
    {
        // No include path matches — return false
        $this->assertFalse(CodeCoverageHelper::shouldIncludeFile('/app/other/Baz.php', ['/app/src', '/app/lib'], []));
    }
}
