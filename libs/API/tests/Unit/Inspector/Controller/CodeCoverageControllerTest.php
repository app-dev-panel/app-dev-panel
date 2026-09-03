<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Debug\Exception\BadRequestException;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Inspector\Controller\CodeCoverageController;
use AppDevPanel\Api\PathResolverInterface;
use AppDevPanel\Kernel\Collector\CodeCoverageCollector;

final class CodeCoverageControllerTest extends ControllerTestCase
{
    private const array COVERAGE = [
        'driver' => 'pcov',
        'files' => ['/srv/app/src/Foo.php' => ['coveredLines' => 3, 'executableLines' => 4, 'percentage' => 75.5]],
        'summary' => ['totalFiles' => 1, 'coveredLines' => 3, 'executableLines' => 4, 'percentage' => 75.5],
    ];

    private function createPathResolver(?string $root = null): PathResolverInterface
    {
        $resolver = $this->createMock(PathResolverInterface::class);
        $resolver->method('getRootPath')->willReturn($root ?? dirname(__DIR__, 5));
        $resolver->method('getRuntimePath')->willReturn(sys_get_temp_dir());

        return $resolver;
    }

    /**
     * @param list<array<string, mixed>> $summaries newest first
     * @param array<string, array<string, mixed>> $details id => detail payload
     */
    private function repository(array $summaries = [], array $details = []): CollectorRepositoryInterface
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($summaries);
        $repository->method('getDetail')->willReturnCallback(static fn(string $id): array => $details[$id] ?? []);

        return $repository;
    }

    private function createController(
        ?CollectorRepositoryInterface $repository = null,
        ?string $driver = 'pcov',
        ?string $root = null,
    ): CodeCoverageController {
        return new CodeCoverageController(
            $this->createResponseFactory(),
            $this->createPathResolver($root),
            $repository,
            static fn(): ?string => $driver,
        );
    }

    public function testIndexWithoutDriver(): void
    {
        $controller = $this->createController($this->repository(), driver: null);
        $response = $controller->index($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertNull($data['driver']);
        $this->assertStringContainsString('pcov or xdebug', $data['error']);
        $this->assertSame([], $data['files']);
        $this->assertSame(0, $data['summary']['totalFiles']);
    }

    public function testIndexWithoutRepositoryIs501(): void
    {
        $controller = $this->createController(null);
        $response = $controller->index($this->get());

        $this->assertSame(501, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('pcov', $data['driver']);
        $this->assertStringContainsString('CollectorRepositoryInterface', $data['error']);
        $this->assertSame([], $data['files']);
    }

    public function testIndexServesNewestEntryWithCoverage(): void
    {
        $repository = $this->repository(summaries: [
            ['id' => 'newest-no-coverage'],
            ['id' => 'with-coverage', 'codeCoverage' => ['percentage' => 75.5]],
            ['id' => 'older', 'codeCoverage' => ['percentage' => 10.0]],
        ], details: ['with-coverage' => [CodeCoverageCollector::class => self::COVERAGE]]);
        $response = $this->createController($repository)->index($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('with-coverage', $data['debugEntryId']);
        $this->assertSame('pcov', $data['driver']);
        $this->assertSame(75.5, $data['summary']['percentage']);
        $this->assertArrayHasKey('/srv/app/src/Foo.php', $data['files']);
    }

    public function testIndexWithExplicitDebugEntryId(): void
    {
        $repository = $this->repository(details: ['abc-123' => [CodeCoverageCollector::class => self::COVERAGE]]);
        $response = $this->createController($repository)->index($this->get(['debugEntryId' => 'abc-123']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc-123', $this->responseData($response)['debugEntryId']);
    }

    public function testIndexRejectsUnsafeDebugEntryId(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->never())->method('getDetail');

        $this->expectException(BadRequestException::class);
        $this->createController($repository)->index($this->get(['debugEntryId' => '../../x']));
    }

    public function testIndexWhenNoEntryHasCoverageIs404(): void
    {
        $repository = $this->repository(summaries: [['id' => 'a'], ['id' => 'b']]);
        $response = $this->createController($repository)->index($this->get());

        $this->assertSame(404, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertStringContainsString('CodeCoverageCollector', $data['error']);
        $this->assertSame([], $data['files']);
        $this->assertSame(0.0, (float) $data['summary']['percentage']);
    }

    public function testIndexWhenEntryLacksCollectorDataIs404(): void
    {
        $repository = $this->repository(details: ['abc' => ['OtherCollector' => []]]);
        $response = $this->createController($repository)->index($this->get(['debugEntryId' => 'abc']));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('abc', $this->responseData($response)['error']);
    }

    public function testIndexNeverStartsCoverageInsideTheInspectorRequest(): void
    {
        // Regression: the endpoint used to start+stop the driver around nothing and report 0%.
        $repository = $this->repository(details: ['abc' => [CodeCoverageCollector::class => self::COVERAGE]]);
        $data = $this->responseData($this->createController($repository)->index($this->get(['debugEntryId' => 'abc'])));

        $this->assertNotSame(0.0, (float) $data['summary']['percentage']);
        $this->assertSame(self::COVERAGE['summary'], $data['summary']);
    }

    public function testFileMissingPath(): void
    {
        $controller = $this->createController();
        $response = $controller->file($this->get());

        $this->assertSame(400, $response->getStatusCode());

        $data = $this->responseData($response);
        $this->assertSame('Missing required parameter: path', $data['message']);
    }

    public function testFileNotFound(): void
    {
        $controller = $this->createController();
        $response = $controller->file($this->get(['path' => '/nonexistent/file.php']));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testFileSuccess(): void
    {
        $controller = $this->createController();
        $response = $controller->file($this->get(['path' => __FILE__]));

        $this->assertSame(200, $response->getStatusCode());

        $data = $this->responseData($response);
        $this->assertSame(realpath(__FILE__), $data['path']);
        $this->assertStringContainsString('CodeCoverageControllerTest', $data['content']);
        $this->assertSame(substr_count(file_get_contents(__FILE__), "\n") + 1, $data['lines']);
    }

    public function testFileOutsideRootReturns403(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-coverage-test-' . uniqid();
        mkdir($tmpDir, 0o755, true);

        try {
            $controller = $this->createController(root: $tmpDir);

            // __FILE__ is outside $tmpDir
            $response = $controller->file($this->get(['path' => __FILE__]));

            $this->assertSame(403, $response->getStatusCode());
            $this->assertSame(
                'Access denied: path is outside the project root.',
                $this->responseData($response)['message'],
            );
        } finally {
            rmdir($tmpDir);
        }
    }

    public function testFileInSiblingDirectoryWithSharedPrefixReturns403(): void
    {
        // root = <base>/srv/app ; requested = <base>/srv/app-backup/.env
        $base = sys_get_temp_dir() . '/adp-coverage-prefix-' . uniqid();
        mkdir($base . '/srv/app', 0o755, true);
        mkdir($base . '/srv/app-backup', 0o755, true);
        file_put_contents($base . '/srv/app-backup/.env', 'SECRET=1');
        file_put_contents($base . '/srv/app/ok.php', '<?php');

        try {
            $controller = $this->createController(root: $base . '/srv/app');

            $denied = $controller->file($this->get(['path' => $base . '/srv/app-backup/.env']));
            $this->assertSame(403, $denied->getStatusCode());
            $this->assertStringNotContainsString('SECRET', (string) $denied->getBody());

            $allowed = $controller->file($this->get(['path' => $base . '/srv/app/ok.php']));
            $this->assertSame(200, $allowed->getStatusCode());
        } finally {
            unlink($base . '/srv/app-backup/.env');
            unlink($base . '/srv/app/ok.php');
            rmdir($base . '/srv/app-backup');
            rmdir($base . '/srv/app');
            rmdir($base . '/srv');
            rmdir($base);
        }
    }

    public function testFileEmptyPathReturns400(): void
    {
        $controller = $this->createController();
        $response = $controller->file($this->get(['path' => '']));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testFileWithDirectoryPathReturns404(): void
    {
        $controller = $this->createController();
        $response = $controller->file($this->get(['path' => __DIR__]));

        $this->assertSame(404, $response->getStatusCode());
    }
}
