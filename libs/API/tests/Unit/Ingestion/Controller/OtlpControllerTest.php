<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Ingestion\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Ingestion\Controller\OtlpController;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\FileStorage;
use GuzzleHttp\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;

final class OtlpControllerTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp-otlp-test-' . uniqid();
        mkdir($this->storagePath, 0o755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->storagePath);
    }

    private function createController(): OtlpController
    {
        $responseFactory = $this->createJsonResponseFactory();
        $storage = new FileStorage($this->storagePath, new DebuggerIdGenerator());

        return new OtlpController($responseFactory, $storage);
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
        $request = new ServerRequest('POST', '/debug/api/otlp/v1/traces');
        return $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Stream::create(json_encode($body, JSON_THROW_ON_ERROR)));
    }

    public function testTracesMinimalSpan(): void
    {
        $controller = $this->createController();

        $response = $controller->traces($this->post([
            'resourceSpans' => [
                [
                    'resource' => [
                        'attributes' => [
                            ['key' => 'service.name', 'value' => ['stringValue' => 'test-service']],
                        ],
                    ],
                    'scopeSpans' => [
                        [
                            'spans' => [
                                [
                                    'traceId' => 'aaaa1111bbbb2222cccc3333dddd4444',
                                    'spanId' => '1111222233334444',
                                    'name' => 'GET /api/users',
                                    'startTimeUnixNano' => '1700000000000000000',
                                    'endTimeUnixNano' => '1700000000150000000',
                                    'kind' => 2,
                                    'status' => ['code' => 1],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]));

        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('partialSuccess', $data);

        // Verify storage has entry
        $files = glob($this->storagePath . '/**/**/summary.json.gz');
        $this->assertCount(1, $files);
    }

    public function testTracesMultipleSpansSameTrace(): void
    {
        $controller = $this->createController();

        $response = $controller->traces($this->post([
            'resourceSpans' => [
                [
                    'resource' => [
                        'attributes' => [
                            ['key' => 'service.name', 'value' => ['stringValue' => 'my-app']],
                        ],
                    ],
                    'scopeSpans' => [
                        [
                            'spans' => [
                                [
                                    'traceId' => 'trace-aaa',
                                    'spanId' => 'span-1',
                                    'name' => 'root',
                                    'startTimeUnixNano' => '0',
                                    'endTimeUnixNano' => '1000000000',
                                    'kind' => 2,
                                ],
                                [
                                    'traceId' => 'trace-aaa',
                                    'spanId' => 'span-2',
                                    'parentSpanId' => 'span-1',
                                    'name' => 'child',
                                    'startTimeUnixNano' => '100000000',
                                    'endTimeUnixNano' => '500000000',
                                    'kind' => 3,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]));

        $this->assertSame(200, $response->getStatusCode());

        // Should create one entry per trace
        $files = glob($this->storagePath . '/**/**/summary.json.gz');
        $this->assertCount(1, $files);
    }

    public function testTracesEmptyPayload(): void
    {
        $controller = $this->createController();

        $response = $controller->traces($this->post([
            'resourceSpans' => [],
        ]));

        $this->assertSame(200, $response->getStatusCode());

        // No entries should be created
        $files = glob($this->storagePath . '/**/**/summary.json.gz');
        $this->assertCount(0, $files);
    }

    public function testTracesMultipleTraces(): void
    {
        $controller = $this->createController();

        $response = $controller->traces($this->post([
            'resourceSpans' => [
                [
                    'resource' => ['attributes' => []],
                    'scopeSpans' => [
                        [
                            'spans' => [
                                [
                                    'traceId' => 'trace-1',
                                    'spanId' => 'span-a',
                                    'name' => 'op-a',
                                    'startTimeUnixNano' => '0',
                                    'endTimeUnixNano' => '0',
                                ],
                                [
                                    'traceId' => 'trace-2',
                                    'spanId' => 'span-b',
                                    'name' => 'op-b',
                                    'startTimeUnixNano' => '0',
                                    'endTimeUnixNano' => '0',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]));

        $this->assertSame(200, $response->getStatusCode());

        // Two traces = two entries
        $files = glob($this->storagePath . '/**/**/summary.json.gz');
        $this->assertCount(2, $files);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
