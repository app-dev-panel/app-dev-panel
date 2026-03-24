<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Ingestion\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Ingestion\Controller\RayController;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\FileStorage;
use GuzzleHttp\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;

final class RayControllerTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp-ray-test-' . uniqid();
        mkdir($this->storagePath, 0o755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->storagePath);
    }

    private function createController(): RayController
    {
        $responseFactory = $this->createJsonResponseFactory();
        $storage = new FileStorage($this->storagePath, new DebuggerIdGenerator());

        return new RayController($responseFactory, $storage);
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
        $request = new ServerRequest('POST', '/_ray/api/events');
        return $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Stream::create(json_encode($body, JSON_THROW_ON_ERROR)));
    }

    public function testAvailabilityReturns400(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', '/_ray/api/availability');
        $response = $controller->availability($request);

        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['active']);
    }

    public function testEventWithLogPayload(): void
    {
        $controller = $this->createController();
        $response = $controller->event($this->post([
            'uuid' => 'ray-test-123',
            'payloads' => [
                [
                    'type' => 'log',
                    'content' => ['values' => ['Hello from Ray!']],
                    'origin' => ['file' => '/app/test.php', 'line_number' => 42],
                ],
            ],
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['success']);

        // Verify stored data
        $dataFiles = glob($this->storagePath . '/**/ray-test-123/data.json');
        $this->assertCount(1, $dataFiles);
        $stored = json_decode(file_get_contents($dataFiles[0]), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('var-dumper', $stored);
        $this->assertSame(['Hello from Ray!'], $stored['var-dumper'][0]['variable']);
        $this->assertSame('/app/test.php:42', $stored['var-dumper'][0]['line']);
    }

    public function testEventWithMultiplePayloads(): void
    {
        $controller = $this->createController();
        $response = $controller->event($this->post([
            'payloads' => [
                [
                    'type' => 'log',
                    'content' => ['values' => 'first'],
                    'origin' => [],
                ],
                [
                    'type' => 'custom',
                    'content' => ['content' => '<div>HTML</div>'],
                    'origin' => ['file' => '/app/view.php', 'line_number' => 10],
                ],
            ],
        ]));

        $this->assertSame(200, $response->getStatusCode());

        // Verify summary has correct count
        $summaryFiles = glob($this->storagePath . '/**/**/summary.json');
        $this->assertCount(1, $summaryFiles);
        $summary = json_decode(file_get_contents($summaryFiles[0]), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(2, $summary['var-dumper']['total']);
    }

    public function testEventWithEmptyPayloads(): void
    {
        $controller = $this->createController();
        $response = $controller->event($this->post([
            'payloads' => [],
        ]));

        $this->assertSame(200, $response->getStatusCode());

        // No data should be stored
        $dataFiles = glob($this->storagePath . '/**/**/data.json');
        $this->assertCount(0, $dataFiles);
    }

    public function testEventExceptionPayload(): void
    {
        $controller = $this->createController();
        $response = $controller->event($this->post([
            'payloads' => [
                [
                    'type' => 'exception',
                    'content' => ['class' => 'RuntimeException', 'message' => 'Something broke'],
                    'origin' => ['file' => '/app/handler.php', 'line_number' => 99],
                ],
            ],
        ]));

        $this->assertSame(200, $response->getStatusCode());

        $dataFiles = glob($this->storagePath . '/**/**/data.json');
        $stored = json_decode(file_get_contents($dataFiles[0]), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('RuntimeException: Something broke', $stored['var-dumper'][0]['variable']);
    }

    public function testLockStatusReturnsInactive(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', '/_ray/api/locks/abc123');
        $response = $controller->lockStatus($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($data['active']);
        $this->assertFalse($data['stop_execution']);
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
