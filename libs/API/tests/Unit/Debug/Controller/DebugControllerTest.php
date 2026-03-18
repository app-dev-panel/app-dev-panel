<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Debug\Controller;

use AppDevPanel\Api\Debug\Controller\DebugController;
use AppDevPanel\Api\Debug\Exception\NotFoundException;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Kernel\Storage\StorageInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

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

        $controller = $this->createController($repository);
        $request = new ServerRequest('GET', '/debug/api');
        $response = $controller->index($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSummary(): void
    {
        $summaryData = ['id' => '123', 'url' => '/test'];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getSummary')->with('123')->willReturn($summaryData);

        $controller = $this->createController($repository);
        $request = new ServerRequest('GET', '/test')->withAttribute('id', '123');
        $response = $controller->summary($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testView(): void
    {
        $detailData = [
            'AppDevPanel\\Kernel\\Collector\\LogCollector' => ['logs' => []],
            'AppDevPanel\\Kernel\\Collector\\EventCollector' => ['events' => []],
        ];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getDetail')->with('123')->willReturn($detailData);

        $controller = $this->createController($repository);
        $request = new ServerRequest('GET', '/test')->withAttribute('id', '123');
        $response = $controller->view($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testViewWithCollector(): void
    {
        $detailData = [
            'AppDevPanel\\Kernel\\Collector\\LogCollector' => ['logs' => [['level' => 'info']]],
            'AppDevPanel\\Kernel\\Collector\\EventCollector' => ['events' => []],
        ];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getDetail')->with('123')->willReturn($detailData);

        $controller = $this->createController($repository);
        $request = new ServerRequest('GET', '/test')
            ->withAttribute('id', '123')
            ->withQueryParams(['collector' => 'AppDevPanel\Kernel\Collector\LogCollector']);
        $response = $controller->view($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testViewWithCollectorNotFound(): void
    {
        $detailData = [
            'AppDevPanel\\Kernel\\Collector\\LogCollector' => ['logs' => []],
        ];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getDetail')->with('123')->willReturn($detailData);

        $controller = $this->createController($repository);
        $request = new ServerRequest('GET', '/test')
            ->withAttribute('id', '123')
            ->withQueryParams(['collector' => 'NonExistent']);

        $this->expectException(NotFoundException::class);
        $controller->view($request);
    }

    public function testDump(): void
    {
        $dumpData = ['class' => 'stdClass', 'properties' => ['name' => 'test']];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getDumpObject')->with('123')->willReturn($dumpData);

        $controller = $this->createController($repository);
        $request = new ServerRequest('GET', '/test')->withAttribute('id', '123');
        $response = $controller->dump($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDumpWithCollector(): void
    {
        $dumpData = ['collector1' => ['data']];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getDumpObject')->with('123')->willReturn($dumpData);

        $controller = $this->createController($repository);
        $request = new ServerRequest('GET', '/test')
            ->withAttribute('id', '123')
            ->withQueryParams(['collector' => 'collector1']);
        $response = $controller->dump($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDumpWithCollectorNotFound(): void
    {
        $dumpData = ['collector1' => ['data']];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getDumpObject')->with('123')->willReturn($dumpData);

        $controller = $this->createController($repository);
        $request = new ServerRequest('GET', '/test')
            ->withAttribute('id', '123')
            ->withQueryParams(['collector' => 'nonexistent']);

        $this->expectException(NotFoundException::class);
        $controller->dump($request);
    }

    public function testObject(): void
    {
        $objectData = ['stdClass', ['name' => 'test']];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getObject')->with('123', '456')->willReturn($objectData);

        $controller = $this->createController($repository);
        $request = new ServerRequest('GET', '/test')
            ->withAttribute('id', '123')
            ->withAttribute('objectId', '456');
        $response = $controller->object($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testObjectNotFound(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getObject')->with('123', '999')->willReturn(null);

        $controller = $this->createController($repository);
        $request = new ServerRequest('GET', '/test')
            ->withAttribute('id', '123')
            ->withAttribute('objectId', '999');

        $this->expectException(NotFoundException::class);
        $controller->object($request);
    }

    private function createJsonResponseFactory(): JsonResponseFactoryInterface
    {
        $factory = $this->createMock(JsonResponseFactoryInterface::class);
        $factory
            ->method('createJsonResponse')
            ->willReturnCallback(function (mixed $data, int $status = 200): Response {
                return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
            });
        return $factory;
    }

    private function createController(?CollectorRepositoryInterface $repository = null): DebugController
    {
        $repository ??= $this->createMock(CollectorRepositoryInterface::class);
        $storage = $this->createMock(StorageInterface::class);
        $psr17 = new Psr17Factory();

        return new DebugController($this->createJsonResponseFactory(), $repository, $storage, $psr17);
    }
}
