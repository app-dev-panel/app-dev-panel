<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Controller;

use AppDevPanel\Adapter\Yii2\Controller\DebugQueryController;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use PHPUnit\Framework\TestCase;
use yii\console\Application;
use yii\console\ExitCode;

final class DebugQueryControllerTest extends TestCase
{
    protected function setUp(): void
    {
        \Yii::$container = new \yii\di\Container();

        $storagePath = sys_get_temp_dir() . '/adp_query_test_' . bin2hex(random_bytes(4));
        mkdir($storagePath, 0o777, true);

        new Application([
            'id' => 'test',
            'basePath' => $storagePath,
        ]);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
        \Yii::$container = new \yii\di\Container();
        \Yii::$app = null;
    }

    public function testListEmpty(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn([]);

        $controller = $this->createController($repository);
        $result = $controller->runAction('list');

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testListWithEntries(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn([
            [
                'id' => 'abc-123',
                'request' => ['method' => 'GET', 'url' => '/test', 'responseStatusCode' => '200'],
            ],
        ]);

        $controller = $this->createController($repository);
        $result = $controller->runAction('list');

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testListWithLimit(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn([
            ['id' => '1'],
            ['id' => '2'],
            ['id' => '3'],
        ]);

        $controller = $this->createController($repository);
        $result = $controller->actionList(limit: 2);

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testViewReturnsOk(): void
    {
        $data = [
            'AppDevPanel\\Kernel\\Collector\\LogCollector' => ['entries' => []],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDetail')->with('abc-123')->willReturn($data);

        $controller = $this->createController($repository);
        $result = $controller->actionView('abc-123');

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testViewWithCollectorFilter(): void
    {
        $collectorClass = 'AppDevPanel\\Kernel\\Collector\\LogCollector';
        $data = [
            $collectorClass => ['entries' => ['log1', 'log2']],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDetail')->with('abc-123')->willReturn($data);

        $controller = $this->createController($repository);
        $result = $controller->actionView('abc-123', collector: $collectorClass);

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testViewWithUnknownCollector(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDetail')->willReturn([
            'AppDevPanel\\Kernel\\Collector\\LogCollector' => [],
        ]);

        $controller = $this->createController($repository);
        $result = $controller->actionView('abc-123', collector: 'NonExistent');

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $result);
    }

    public function testViewNotFound(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDetail')->willThrowException(new \RuntimeException('Not found'));

        $controller = $this->createController($repository);
        $result = $controller->actionView('nonexistent');

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $result);
    }

    public function testDefaultActionIsList(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $controller = $this->createController($repository);

        $this->assertSame('list', $controller->defaultAction);
    }

    private function createController(CollectorRepositoryInterface $repository): DebugQueryController
    {
        return new DebugQueryController('debug-query', \Yii::$app, $repository);
    }
}
