<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Ingestion\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Ingestion\Controller\IngestionController;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\SqliteStorage;
use AppDevPanel\Kernel\Storage\StorageInterface;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;

final class IngestionControllerTest extends TestCase
{
    private string $storagePath;
    private SqliteStorage $storage;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp-ingestion-test-' . uniqid();
        mkdir($this->storagePath, 0o755, true);
        $this->storage = new SqliteStorage($this->storagePath . '/debug.db', new DebuggerIdGenerator());
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->storagePath);
    }

    private function createController(): IngestionController
    {
        $responseFactory = $this->createJsonResponseFactory();

        return new IngestionController($responseFactory, $this->storage);
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

    private function post(array $body): ServerRequest
    {
        $request = new ServerRequest('POST', '/test');
        return $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Stream::create(json_encode($body, JSON_THROW_ON_ERROR)));
    }

    public function testIngestMinimal(): void
    {
        $controller = $this->createController();
        $response = $controller->ingest($this->post([
            'collectors' => [
                'logs' => [
                    ['level' => 'info', 'message' => 'Hello from Python'],
                ],
            ],
        ]));

        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['id']);

        // Verify entry was written
        $summaries = $this->storage->read(StorageInterface::TYPE_SUMMARY);
        $this->assertCount(1, $summaries);
    }

    public function testIngestWithContext(): void
    {
        $controller = $this->createController();
        $response = $controller->ingest($this->post([
            'debugId' => 'external-123',
            'context' => [
                'type' => 'web',
                'language' => 'python',
                'service' => 'my-api',
                'request' => [
                    'method' => 'POST',
                    'uri' => '/api/users',
                    'statusCode' => 201,
                    'duration' => 0.045,
                ],
            ],
            'collectors' => [
                'logs' => [
                    ['level' => 'info', 'message' => 'User created'],
                ],
                'http_client' => [
                    ['method' => 'POST', 'uri' => 'https://db.local/insert', 'totalTime' => 0.012],
                ],
            ],
            'summary' => [
                'logger' => ['total' => 1],
                'http' => ['count' => 1],
            ],
        ]));

        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('external-123', $data['id']);

        // Verify summary has context
        $summaries = $this->storage->read(StorageInterface::TYPE_SUMMARY, 'external-123');
        $this->assertCount(1, $summaries);
        $summary = $summaries['external-123'];
        $this->assertSame('web', $summary['context']['type']);
        $this->assertSame('python', $summary['context']['language']);
    }

    public function testIngestMissingCollectors(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('collectors');
        $controller->ingest($this->post(['summary' => []]));
    }

    public function testIngestBatch(): void
    {
        $controller = $this->createController();
        $response = $controller->ingestBatch($this->post([
            'entries' => [
                ['collectors' => ['logs' => [['level' => 'info', 'message' => 'Entry 1']]]],
                ['collectors' => ['logs' => [['level' => 'error', 'message' => 'Entry 2']]]],
                ['collectors' => ['logs' => [['level' => 'debug', 'message' => 'Entry 3']]]],
            ],
        ]));

        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(3, $data['count']);
        $this->assertCount(3, $data['ids']);

        // Verify 3 entries in storage
        $summaries = $this->storage->read(StorageInterface::TYPE_SUMMARY);
        $this->assertCount(3, $summaries);
    }

    public function testIngestBatchMissingEntries(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('entries');
        $controller->ingestBatch($this->post([]));
    }

    public function testIngestBatchInvalidEntry(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('collectors');
        $controller->ingestBatch($this->post([
            'entries' => [
                ['summary' => ['no collectors here']],
            ],
        ]));
    }

    public function testIngestLog(): void
    {
        $controller = $this->createController();
        $response = $controller->ingestLog($this->post([
            'level' => 'error',
            'message' => 'Connection refused',
            'context' => ['host' => 'db.local'],
            'line' => 'database.py:42',
            'service' => 'my-backend',
        ]));

        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['success']);

        // Verify data was stored
        $entries = $this->storage->read(StorageInterface::TYPE_DATA);
        $this->assertCount(1, $entries);
        $stored = array_values($entries)[0];
        $this->assertArrayHasKey('logs', $stored);
        $this->assertSame('error', $stored['logs'][0]['level']);
        $this->assertSame('Connection refused', $stored['logs'][0]['message']);
    }

    public function testIngestLogMissingFields(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('level');
        $controller->ingestLog($this->post(['message' => 'no level']));
    }

    public function testIngestDataReadableViaStorage(): void
    {
        $controller = $this->createController();
        $response = $controller->ingest($this->post([
            'debugId' => 'read-test-001',
            'collectors' => [
                'custom_collector' => [
                    ['key' => 'value', 'timestamp' => 1_710_000_000],
                ],
            ],
        ]));

        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $id = $data['id'];

        // Now read it back via storage
        $summaryAll = $this->storage->read(StorageInterface::TYPE_SUMMARY, null);
        $this->assertArrayHasKey($id, $summaryAll);

        $detail = $this->storage->read(StorageInterface::TYPE_DATA, $id);
        $this->assertArrayHasKey($id, $detail);
        $entryData = $detail[$id];
        $this->assertArrayHasKey('custom_collector', $entryData);
    }

    public function testMultipleCollectorTypes(): void
    {
        $controller = $this->createController();
        $response = $controller->ingest($this->post([
            'collectors' => [
                'logs' => [
                    ['level' => 'info', 'message' => 'Request started'],
                    ['level' => 'debug', 'message' => 'DB query executed'],
                ],
                'http_client' => [
                    ['method' => 'GET', 'uri' => 'https://api.stripe.com/charges', 'totalTime' => 0.5],
                ],
                'exceptions' => [
                    ['class' => 'ValueError', 'message' => 'Invalid amount', 'file' => 'payment.py', 'line' => 55],
                ],
                'custom_metrics' => [
                    ['name' => 'memory_peak', 'value' => 128.5, 'unit' => 'MB'],
                ],
            ],
            'context' => [
                'type' => 'web',
                'language' => 'python',
                'service' => 'payment-service',
            ],
        ]));

        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        // Verify summary contains all collector names
        $summaries = $this->storage->read(StorageInterface::TYPE_SUMMARY, $data['id']);
        $summary = $summaries[$data['id']];
        /** @var list<array{id: string, name: string}> $collectors */
        $collectors = $summary['collectors'];
        $collectorIds = array_column($collectors, 'id');
        $this->assertContains('logs', $collectorIds);
        $this->assertContains('http_client', $collectorIds);
        $this->assertContains('exceptions', $collectorIds);
        $this->assertContains('custom_metrics', $collectorIds);
    }

    public function testIngestBatchExceedsLimit(): void
    {
        $controller = $this->createController();

        $entries = [];
        for ($i = 0; $i < 101; $i++) {
            $entries[] = ['collectors' => ['logs' => []]];
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum 100');
        $controller->ingestBatch($this->post(['entries' => $entries]));
    }

    public function testIngestLogWithMinimalFields(): void
    {
        $controller = $this->createController();
        $response = $controller->ingestLog($this->post([
            'level' => 'debug',
            'message' => 'Simple message',
        ]));

        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['success']);
    }

    public function testIngestLogMissingMessage(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('level');
        $controller->ingestLog($this->post(['level' => 'info']));
    }

    public function testIngestCollectorsNotArrayThrowsException(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('collectors');
        $controller->ingest($this->post(['collectors' => 'not-an-array']));
    }

    public function testIngestBatchEntryWithoutCollectorsThrows(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('collectors');
        $controller->ingestBatch($this->post([
            'entries' => [
                ['context' => ['type' => 'web']],
            ],
        ]));
    }

    public function testIngestLogMissingLevel(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('level');
        $controller->ingestLog($this->post(['message' => 'no level here']));
    }

    public function testIngestBatchEntriesNotArrayThrows(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('entries');
        $controller->ingestBatch($this->post(['entries' => 'not-array']));
    }

    public function testIngestWithCustomDebugId(): void
    {
        $controller = $this->createController();
        $response = $controller->ingest($this->post([
            'debugId' => 'custom-id-abc',
            'collectors' => ['logs' => [['level' => 'info', 'message' => 'test']]],
        ]));

        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('custom-id-abc', $data['id']);
    }

    public function testIngestLogContextDefaults(): void
    {
        $controller = $this->createController();
        $response = $controller->ingestLog($this->post([
            'level' => 'info',
            'message' => 'test',
        ]));

        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        // Verify stored data has default context
        $summaries = $this->storage->read(StorageInterface::TYPE_SUMMARY);
        $this->assertCount(1, $summaries);
        $summary = array_values($summaries)[0];
        $this->assertSame('generic', $summary['context']['type']);
        $this->assertSame('external', $summary['context']['service']);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
