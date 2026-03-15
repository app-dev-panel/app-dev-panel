<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Debug\Controller;

use AppDevPanel\Api\Debug\Controller\DebugController;
use AppDevPanel\Api\Debug\Exception\NotFoundException;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Yiisoft\DataResponse\DataResponseFactory;
use Yiisoft\Router\CurrentRoute;

final class DebugControllerTest extends TestCase
{
    public function testIndex(): void
    {
        $summaryData = [
            ['id' => '1', 'url' => '/test'],
            ['id' => '2', 'url' => '/other'],
        ];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getSummary')->willReturn($summaryData);

        $controller = new DebugController($this->createResponseFactory(), $repository);
        $response = $controller->index();

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSummary(): void
    {
        $summaryData = ['id' => '123', 'url' => '/test'];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getSummary')->with('123')->willReturn($summaryData);

        $currentRoute = $this->createCurrentRoute(['id' => '123']);

        $controller = new DebugController($this->createResponseFactory(), $repository);
        $response = $controller->summary($currentRoute);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDump(): void
    {
        $dumpData = ['class' => 'stdClass', 'properties' => ['name' => 'test']];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getDumpObject')->with('123')->willReturn($dumpData);

        $currentRoute = $this->createCurrentRoute(['id' => '123']);

        $controller = new DebugController($this->createResponseFactory(), $repository);
        $response = $controller->dump($currentRoute);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDumpWithCollector(): void
    {
        $dumpData = ['collector1' => ['data']];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getDumpObject')->with('123')->willReturn($dumpData);

        $currentRoute = $this->createCurrentRoute(['id' => '123', 'collector' => 'collector1']);

        $controller = new DebugController($this->createResponseFactory(), $repository);
        $response = $controller->dump($currentRoute);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDumpWithCollectorNotFound(): void
    {
        $dumpData = ['collector1' => ['data']];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getDumpObject')->with('123')->willReturn($dumpData);

        $currentRoute = $this->createCurrentRoute(['id' => '123', 'collector' => 'nonexistent']);

        $controller = new DebugController($this->createResponseFactory(), $repository);

        $this->expectException(NotFoundException::class);
        $controller->dump($currentRoute);
    }

    public function testObject(): void
    {
        $objectData = ['stdClass', ['name' => 'test']];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getObject')->with('123', '456')->willReturn($objectData);

        $currentRoute = $this->createCurrentRoute(['id' => '123', 'objectId' => '456']);

        $controller = new DebugController($this->createResponseFactory(), $repository);
        $response = $controller->object($currentRoute);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testObjectNotFound(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getObject')->with('123', '999')->willReturn(null);

        $currentRoute = $this->createCurrentRoute(['id' => '123', 'objectId' => '999']);

        $controller = new DebugController($this->createResponseFactory(), $repository);

        $this->expectException(NotFoundException::class);
        $controller->object($currentRoute);
    }

    private function createResponseFactory(): DataResponseFactory
    {
        $psr17 = new Psr17Factory();
        return new DataResponseFactory($psr17, $psr17);
    }

    private function createCurrentRoute(array $arguments): CurrentRoute
    {
        $currentRoute = new CurrentRoute();
        $property = new ReflectionProperty(CurrentRoute::class, 'arguments');
        $property->setValue($currentRoute, $arguments);
        return $currentRoute;
    }
}
