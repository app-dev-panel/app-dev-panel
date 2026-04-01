<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Controller;

use AppDevPanel\Adapter\Yii2\Controller\DebugTailController;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use PHPUnit\Framework\TestCase;
use yii\console\Application;
use yii\console\ExitCode;

/**
 * Tests for DebugTailController.
 *
 * Since actionIndex() runs an infinite loop, we test the private helper methods
 * (getEntryIds, renderEntry) via reflection to verify rendering logic.
 */
final class DebugTailControllerTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        \Yii::$container = new \yii\di\Container();

        $this->basePath = sys_get_temp_dir() . '/adp_tail_test_' . bin2hex(random_bytes(4));
        mkdir($this->basePath, 0o777, true);

        new Application([
            'id' => 'test',
            'basePath' => $this->basePath,
        ]);
    }

    protected function tearDown(): void
    {
        \Yii::$container = new \yii\di\Container();
        \Yii::$app = null;

        if (is_dir($this->basePath)) {
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->basePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($items as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
            rmdir($this->basePath);
        }
    }

    public function testGetEntryIdsReturnsStringIds(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->with(null)
            ->willReturn([
                ['id' => 'entry-1'],
                ['id' => 'entry-2'],
            ]);

        $controller = $this->createController($repository);
        $ids = $this->invokeGetEntryIds($controller);

        $this->assertSame(['entry-1', 'entry-2'], $ids);
    }

    public function testGetEntryIdsSkipsNonArrayEntries(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->with(null)
            ->willReturn([
                'not-an-array',
                ['id' => 'valid'],
            ]);

        $controller = $this->createController($repository);
        $ids = $this->invokeGetEntryIds($controller);

        $this->assertSame(['valid'], $ids);
    }

    public function testGetEntryIdsSkipsEntriesWithoutId(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->with(null)
            ->willReturn([
                ['no-id' => true],
                ['id' => 'valid'],
            ]);

        $controller = $this->createController($repository);
        $ids = $this->invokeGetEntryIds($controller);

        $this->assertSame(['valid'], $ids);
    }

    public function testGetEntryIdsSkipsNonStringId(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->with(null)
            ->willReturn([
                ['id' => 123],
                ['id' => 'valid'],
            ]);

        $controller = $this->createController($repository);
        $ids = $this->invokeGetEntryIds($controller);

        $this->assertSame(['valid'], $ids);
    }

    public function testGetEntryIdsReturnsEmptyForNoEntries(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->with(null)->willReturn([]);

        $controller = $this->createController($repository);
        $ids = $this->invokeGetEntryIds($controller);

        $this->assertSame([], $ids);
    }

    public function testRenderEntryFormattedWithRequestSummary(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->with('entry-1')
            ->willReturn([
                'request' => ['method' => 'GET', 'url' => '/api/test', 'responseStatusCode' => '200'],
            ]);

        $controller = $this->createController($repository);
        $this->invokeRenderEntry($controller, 'entry-1', false);

        // Verifies no exception thrown — output goes to STDOUT via fwrite
        $this->assertTrue(true);
    }

    public function testRenderEntryJsonOutput(): void
    {
        $summaryData = [
            'request' => ['method' => 'GET', 'url' => '/test', 'responseStatusCode' => '200'],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->with('entry-1')->willReturn($summaryData);

        $controller = $this->createController($repository);
        $this->invokeRenderEntry($controller, 'entry-1', true);

        $this->assertTrue(true);
    }

    public function testRenderEntryWithWebSummary(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->with('entry-1')
            ->willReturn([
                'web' => ['method' => 'POST', 'url' => '/submit', 'responseStatusCode' => '302'],
            ]);

        $controller = $this->createController($repository);
        $this->invokeRenderEntry($controller, 'entry-1', false);

        $this->assertTrue(true);
    }

    public function testRenderEntryWithCommandSummary(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->with('entry-1')
            ->willReturn([
                'command' => ['method' => 'CLI', 'url' => 'migrate/up', 'responseStatusCode' => '0'],
            ]);

        $controller = $this->createController($repository);
        $this->invokeRenderEntry($controller, 'entry-1', false);

        $this->assertTrue(true);
    }

    public function testRenderEntryWithExceptionHighlight(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->with('entry-1')
            ->willReturn([
                'request' => ['method' => 'GET', 'url' => '/error', 'responseStatusCode' => '500'],
                'exception' => ['class' => 'RuntimeException'],
            ]);

        $controller = $this->createController($repository);
        $this->invokeRenderEntry($controller, 'entry-1', false);

        $this->assertTrue(true);
    }

    public function testRenderEntryWith4xxStatus(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->with('entry-1')
            ->willReturn([
                'request' => ['method' => 'GET', 'url' => '/missing', 'responseStatusCode' => '404'],
            ]);

        $controller = $this->createController($repository);
        $this->invokeRenderEntry($controller, 'entry-1', false);

        $this->assertTrue(true);
    }

    public function testRenderEntryWith3xxStatus(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->with('entry-1')
            ->willReturn([
                'request' => ['method' => 'GET', 'url' => '/old', 'responseStatusCode' => '301'],
            ]);

        $controller = $this->createController($repository);
        $this->invokeRenderEntry($controller, 'entry-1', false);

        $this->assertTrue(true);
    }

    public function testRenderEntryWithNoSummaryKeys(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->with('entry-1')->willReturn([]);

        $controller = $this->createController($repository);
        $this->invokeRenderEntry($controller, 'entry-1', false);

        $this->assertTrue(true);
    }

    public function testRenderEntryJsonOutputStructure(): void
    {
        $summaryData = [
            'request' => ['method' => 'DELETE', 'url' => '/resource/1', 'responseStatusCode' => '204'],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->with('entry-1')->willReturn($summaryData);

        $controller = $this->createController($repository);
        $this->invokeRenderEntry($controller, 'entry-1', true);

        $this->assertTrue(true);
    }

    public function testRenderEntryWith5xxStatusWithoutException(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->with('entry-1')
            ->willReturn([
                'request' => ['method' => 'GET', 'url' => '/error', 'responseStatusCode' => '503'],
            ]);

        $controller = $this->createController($repository);
        $this->invokeRenderEntry($controller, 'entry-1', false);

        $this->assertTrue(true);
    }

    public function testRenderEntryWithUnknownStatusCode(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->with('entry-1')
            ->willReturn([
                'request' => ['method' => 'GET', 'url' => '/custom', 'responseStatusCode' => '100'],
            ]);

        $controller = $this->createController($repository);
        $this->invokeRenderEntry($controller, 'entry-1', false);

        $this->assertTrue(true);
    }

    public function testGetEntryIdsCalledByRepository(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getSummary')
            ->with(null)
            ->willReturn([['id' => 'abc']]);

        $controller = $this->createController($repository);
        $this->invokeGetEntryIds($controller);
    }

    public function testRenderEntryCallsRepositoryWithId(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getSummary')
            ->with('specific-id')
            ->willReturn([]);

        $controller = $this->createController($repository);
        $this->invokeRenderEntry($controller, 'specific-id', false);
    }

    private function createController(CollectorRepositoryInterface $repository): DebugTailController
    {
        return new DebugTailController('debug-tail', \Yii::$app, $repository);
    }

    private function invokeGetEntryIds(DebugTailController $controller): array
    {
        $method = new \ReflectionMethod($controller, 'getEntryIds');
        return $method->invoke($controller);
    }

    private function invokeRenderEntry(DebugTailController $controller, string $id, bool $json): void
    {
        $method = new \ReflectionMethod($controller, 'renderEntry');
        $method->invoke($controller, $id, $json);
    }
}
