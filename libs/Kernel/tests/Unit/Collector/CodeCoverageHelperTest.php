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
}
