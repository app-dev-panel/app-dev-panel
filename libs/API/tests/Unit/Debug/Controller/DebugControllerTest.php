<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Debug\Controller;

use AppDevPanel\Api\Debug\Controller\DebugController;
use AppDevPanel\Api\Debug\Exception\NotFoundException;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\HtmlViewProviderInterface;
use AppDevPanel\Kernel\Storage\StorageInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

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

    public function testViewRendersHtmlForSsrCollector(): void
    {
        $detailData = [
            DebugControllerTestSsrCollectorStub::class => ['logs' => [['level' => 'info', 'message' => 'Hi']]],
        ];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getDetail')->with('123')->willReturn($detailData);

        $controller = $this->createController($repository);
        $request = new ServerRequest('GET', '/test')
            ->withAttribute('id', '123')
            ->withQueryParams(['collector' => DebugControllerTestSsrCollectorStub::class]);
        $response = $controller->view($request);

        $this->assertSame(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $payload = json_decode($body, true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('__html', $payload);
        $this->assertStringContainsString('SSR-LOGS-1', $payload['__html']);
        $this->assertStringContainsString('Hi', $payload['__html']);
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

    public function testEventStreamReturnsCorrectHeaders(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('read')->willReturn([]);

        $controller = $this->createController(storage: $storage);
        $request = new ServerRequest('GET', '/debug/api/event-stream');
        $response = $controller->eventStream($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/event-stream', $response->getHeaderLine('Content-Type'));
        $this->assertSame('no-cache', $response->getHeaderLine('Cache-Control'));
        $this->assertSame('keep-alive', $response->getHeaderLine('Connection'));
    }

    public function testEventStreamBodyIsServerSentEventsStream(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('read')->willReturn([]);

        $controller = $this->createController(storage: $storage);
        $request = new ServerRequest('GET', '/debug/api/event-stream');
        $response = $controller->eventStream($request);

        $this->assertInstanceOf(\AppDevPanel\Api\ServerSentEventsStream::class, $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RequiresPhpExtension('sockets')]
    public function testEventStreamEmitsEventOnBroadcast(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', '/debug/api/event-stream');
        $response = $controller->eventStream($request);

        // Broadcast a message so the stream picks it up
        $broadcaster = new \AppDevPanel\Kernel\DebugServer\Broadcaster();
        $broadcaster->broadcast(\AppDevPanel\Kernel\DebugServer\Connection::MESSAGE_TYPE_ENTRY_CREATED, 'test-id');

        $body = $response->getBody();
        $output = $body->read(8192);
        $body->close();

        $this->assertStringContainsString('data: ', $output);
        $this->assertStringContainsString('"type":"entry-created"', $output);
    }

    #[\PHPUnit\Framework\Attributes\RequiresPhpExtension('sockets')]
    public function testEventStreamNoEventWhenNoBroadcast(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', '/debug/api/event-stream');
        $response = $controller->eventStream($request);

        $body = $response->getBody();
        $output = $body->read(8192);
        $body->close();

        // Only heartbeat newlines, no data events
        $this->assertStringNotContainsString('"type":', $output);
    }

    #[\PHPUnit\Framework\Attributes\RequiresPhpExtension('sockets')]
    public function testEventStreamDoesNotReadStorage(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects($this->never())->method('read');

        $controller = $this->createController(storage: $storage);
        $request = new ServerRequest('GET', '/debug/api/event-stream');
        $response = $controller->eventStream($request);

        $body = $response->getBody();
        $body->read(8192);
        $body->close();
    }

    #[\PHPUnit\Framework\Attributes\RequiresPhpExtension('sockets')]
    public function testEventStreamPayloadFormat(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', '/debug/api/event-stream');
        $response = $controller->eventStream($request);

        $broadcaster = new \AppDevPanel\Kernel\DebugServer\Broadcaster();
        $broadcaster->broadcast(
            \AppDevPanel\Kernel\DebugServer\Connection::MESSAGE_TYPE_ENTRY_CREATED,
            'payload-test-id',
        );

        $body = $response->getBody();
        $output = $body->read(8192);
        $body->close();

        // Extract JSON from "data: {...}\n"
        preg_match('/^data: (.+)$/m', $output, $matches);
        $this->assertNotEmpty($matches);

        $payload = json_decode($matches[1], true);
        $this->assertSame('entry-created', $payload['type']);
        $this->assertSame(['id' => 'payload-test-id'], $payload['payload']);
    }

    public function testDumpWithoutCollector(): void
    {
        $dumpData = ['collector1' => ['data'], 'collector2' => ['other']];

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->expects($this->once())->method('getDumpObject')->with('123')->willReturn($dumpData);

        $controller = $this->createController($repository);
        $request = new ServerRequest('GET', '/test')->withAttribute('id', '123');
        $response = $controller->dump($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    private function createJsonResponseFactory(): JsonResponseFactoryInterface
    {
        $factory = $this->createMock(JsonResponseFactoryInterface::class);
        $factory
            ->method('createJsonResponse')
            ->willReturnCallback(static function (mixed $data, int $status = 200): Response {
                return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
            });
        return $factory;
    }

    private function createController(
        ?CollectorRepositoryInterface $repository = null,
        ?StorageInterface $storage = null,
    ): DebugController {
        $repository ??= $this->createMock(CollectorRepositoryInterface::class);
        $storage ??= $this->createMock(StorageInterface::class);
        $psr17 = new Psr17Factory();

        return new DebugController($this->createJsonResponseFactory(), $repository, $storage, $psr17);
    }
}

/**
 * Tiny SSR collector used to drive {@see DebugControllerTest::testViewRendersHtmlForSsrCollector}.
 * Its view template lives at {@see __DIR__}/Fixture/ssr-collector.php.
 */
final class DebugControllerTestSsrCollectorStub implements HtmlViewProviderInterface
{
    use CollectorTrait;

    public function getCollected(): array
    {
        return [];
    }

    private function reset(): void {}

    public static function getViewPath(): string
    {
        return __DIR__ . '/Fixture/ssr-collector.php';
    }
}
