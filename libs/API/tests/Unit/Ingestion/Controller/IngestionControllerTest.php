<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Ingestion\Controller;

use AppDevPanel\Api\Ingestion\Controller\IngestionController;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\FileStorage;
use InvalidArgumentException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Yiisoft\DataResponse\DataResponseFactory;
use Yiisoft\Json\Json;

final class IngestionControllerTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp-ingestion-test-' . uniqid();
        mkdir($this->storagePath, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->storagePath);
    }

    private function createController(): IngestionController
    {
        $psr17 = new Psr17Factory();
        $responseFactory = new DataResponseFactory($psr17, $psr17);
        $storage = new FileStorage($this->storagePath, new DebuggerIdGenerator());

        return new IngestionController($responseFactory, $storage);
    }

    private function post(array $body): ServerRequest
    {
        $request = new ServerRequest('POST', '/test');
        return $request->withHeader('Content-Type', 'application/json')->withBody(Stream::create(Json::encode($body)));
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
        $data = $response->getData();
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['id']);

        // Verify files were written
        $files = glob($this->storagePath . '/**/**/summary.json');
        $this->assertCount(1, $files);
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
        $data = $response->getData();
        $this->assertSame('external-123', $data['id']);

        // Verify summary has context
        $summaryFiles = glob($this->storagePath . '/**/external-123/summary.json');
        $this->assertCount(1, $summaryFiles);
        $summary = Json::decode(file_get_contents($summaryFiles[0]));
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
        $data = $response->getData();
        $this->assertSame(3, $data['count']);
        $this->assertCount(3, $data['ids']);

        // Verify 3 entries in storage
        $files = glob($this->storagePath . '/**/**/summary.json');
        $this->assertCount(3, $files);
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
        $data = $response->getData();
        $this->assertTrue($data['success']);

        // Verify data was stored
        $dataFiles = glob($this->storagePath . '/**/**/data.json');
        $this->assertCount(1, $dataFiles);
        $stored = Json::decode(file_get_contents($dataFiles[0]));
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
                    ['key' => 'value', 'timestamp' => 1710000000],
                ],
            ],
        ]));

        $data = $response->getData();
        $id = $data['id'];

        // Now read it back via storage
        $storage = new FileStorage($this->storagePath, new DebuggerIdGenerator());
        $summaryAll = $storage->read('summary', null);
        $this->assertArrayHasKey($id, $summaryAll);

        $detail = $storage->read('data', $id);
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
        $data = $response->getData();

        // Verify summary contains all collector names
        $summaryFiles = glob($this->storagePath . '/**/' . $data['id'] . '/summary.json');
        $summary = Json::decode(file_get_contents($summaryFiles[0]));
        $this->assertContains('logs', $summary['collectors']);
        $this->assertContains('http_client', $summary['collectors']);
        $this->assertContains('exceptions', $summary['collectors']);
        $this->assertContains('custom_metrics', $summary['collectors']);
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
