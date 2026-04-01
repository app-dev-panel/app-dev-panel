<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Controller;

use AppDevPanel\Adapter\Yii2\Controller\DebugDumpController;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use PHPUnit\Framework\TestCase;
use yii\console\Application;
use yii\console\ExitCode;

final class DebugDumpControllerTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        \Yii::$container = new \yii\di\Container();

        $this->basePath = sys_get_temp_dir() . '/adp_dump_test_' . bin2hex(random_bytes(4));
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

    public function testActionIndexWithData(): void
    {
        $dumpData = [
            'AppDevPanel\\Kernel\\Collector\\VarDumperCollector' => [
                ['id' => 'obj-1', 'class' => 'stdClass'],
            ],
        ];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getDumpObject')->with('entry-1')->willReturn($dumpData);

        $controller = $this->createController($repository);

        ob_start();
        $result = $controller->actionIndex('entry-1');
        ob_end_clean();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionIndexWithEmptyData(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDumpObject')->with('entry-1')->willReturn([]);

        $controller = $this->createController($repository);

        ob_start();
        $result = $controller->actionIndex('entry-1');
        ob_end_clean();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionIndexWithJsonOutput(): void
    {
        $dumpData = [
            'AppDevPanel\\Kernel\\Collector\\VarDumperCollector' => [
                ['id' => 'obj-1', 'class' => 'stdClass'],
            ],
        ];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDumpObject')->willReturn($dumpData);

        $controller = $this->createController($repository);

        ob_start();
        $result = $controller->actionIndex('entry-1', json: true);
        ob_end_clean();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionIndexWithCollectorFilter(): void
    {
        $collectorClass = 'AppDevPanel\\Kernel\\Collector\\VarDumperCollector';
        $dumpData = [
            $collectorClass => [['id' => 'obj-1']],
            'AppDevPanel\\Kernel\\Collector\\LogCollector' => [['id' => 'obj-2']],
        ];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDumpObject')->willReturn($dumpData);

        $controller = $this->createController($repository);

        ob_start();
        $result = $controller->actionIndex('entry-1', collector: $collectorClass);
        ob_end_clean();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionIndexWithUnknownCollector(): void
    {
        $dumpData = [
            'AppDevPanel\\Kernel\\Collector\\VarDumperCollector' => [['id' => 'obj-1']],
        ];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDumpObject')->willReturn($dumpData);

        $controller = $this->createController($repository);

        ob_start();
        $result = $controller->actionIndex('entry-1', collector: 'NonExistent');
        ob_end_clean();

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $result);
    }

    public function testActionIndexHandlesException(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDumpObject')->willThrowException(new \RuntimeException('Not found'));

        $controller = $this->createController($repository);

        ob_start();
        $result = $controller->actionIndex('nonexistent');
        ob_end_clean();

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $result);
    }

    public function testActionObjectReturnsOk(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getObject')
            ->with('entry-1', 'obj-1')
            ->willReturn(['stdClass', ['property' => 'value']]);

        $controller = $this->createController($repository);

        ob_start();
        $result = $controller->actionObject('entry-1', 'obj-1');
        ob_end_clean();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionObjectWithJsonOutput(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getObject')->willReturn(['stdClass', ['property' => 'value']]);

        $controller = $this->createController($repository);

        ob_start();
        $result = $controller->actionObject('entry-1', 'obj-1', json: true);
        ob_end_clean();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionObjectNotFound(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getObject')->willReturn(null);

        $controller = $this->createController($repository);

        ob_start();
        $result = $controller->actionObject('entry-1', 'nonexistent');
        ob_end_clean();

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $result);
    }

    public function testActionObjectHandlesException(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getObject')->willThrowException(new \RuntimeException('Storage error'));

        $controller = $this->createController($repository);

        ob_start();
        $result = $controller->actionObject('entry-1', 'obj-1');
        ob_end_clean();

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $result);
    }

    public function testActionIndexEmptyDataWithJson(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDumpObject')->willReturn([]);

        $controller = $this->createController($repository);

        ob_start();
        $result = $controller->actionIndex('entry-1', json: true);
        ob_end_clean();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionIndexCollectorFilterWithJson(): void
    {
        $collectorClass = 'AppDevPanel\\Kernel\\Collector\\VarDumperCollector';
        $dumpData = [
            $collectorClass => [['id' => 'obj-1']],
        ];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDumpObject')->willReturn($dumpData);

        $controller = $this->createController($repository);

        ob_start();
        $result = $controller->actionIndex('entry-1', collector: $collectorClass, json: true);
        ob_end_clean();

        $this->assertSame(ExitCode::OK, $result);
    }

    private function createController(CollectorRepositoryInterface $repository): DebugDumpController
    {
        return new DebugDumpController('debug-dump', \Yii::$app, $repository);
    }
}
