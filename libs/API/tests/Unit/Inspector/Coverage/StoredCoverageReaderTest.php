<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Coverage;

use AppDevPanel\Api\Debug\Exception\BadRequestException;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Inspector\Coverage\StoredCoverageReader;
use AppDevPanel\Kernel\Collector\CodeCoverageCollector;
use PHPUnit\Framework\TestCase;

final class StoredCoverageReaderTest extends TestCase
{
    public function testResolveEntryIdPrefersExplicitId(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->never())->method('getSummary');

        $this->assertSame('abc-1', new StoredCoverageReader($repository)->resolveEntryId('abc-1'));
    }

    public function testResolveEntryIdRejectsUnsafeExplicitId(): void
    {
        $reader = new StoredCoverageReader($this->createMock(CollectorRepositoryInterface::class));

        $this->expectException(BadRequestException::class);
        $reader->resolveEntryId('../x');
    }

    public function testResolveEntryIdFallsBackToNewestSummaryWithCoverage(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->willReturn([
                ['id' => 'newest'],
                ['id' => 'bad/id', 'codeCoverage' => []],
                ['id' => 'good', 'codeCoverage' => ['percentage' => 1.0]],
                ['id' => 'older', 'codeCoverage' => []],
            ]);

        $reader = new StoredCoverageReader($repository);

        $this->assertSame('good', $reader->resolveEntryId(null));
        $this->assertSame('good', $reader->resolveEntryId(''));
        $this->assertSame('good', $reader->resolveEntryId(42));
    }

    public function testResolveEntryIdReturnsNullWhenNothingHasCoverage(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn([['id' => 'a']]);

        $this->assertNull(new StoredCoverageReader($repository)->resolveEntryId(null));
    }

    public function testReadNormalisesPayload(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getDetail')
            ->willReturnMap([
                [
                    'full',
                    [
                        CodeCoverageCollector::class => [
                            'driver' => 'xdebug',
                            'files' => ['f' => []],
                            'summary' => ['percentage' => 5.0],
                        ],
                    ],
                ],
                ['partial', [CodeCoverageCollector::class => ['driver' => 7]]],
                ['none', ['Other' => []]],
            ]);

        $reader = new StoredCoverageReader($repository);

        $this->assertSame(
            ['driver' => 'xdebug', 'files' => ['f' => []], 'summary' => ['percentage' => 5.0]],
            $reader->read('full'),
        );

        $partial = $reader->read('partial');
        $this->assertNotNull($partial);
        $this->assertNull($partial['driver']);
        $this->assertSame([], $partial['files']);
        $this->assertSame(0, $partial['summary']['totalFiles']);

        $this->assertNull($reader->read('none'));
    }
}
