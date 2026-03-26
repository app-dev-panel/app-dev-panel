<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Tests\Unit\Transport;

use AppDevPanel\TaskBus\Transport\JsonRpcError;
use AppDevPanel\TaskBus\Transport\JsonRpcResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonRpcResponse::class)]
final class JsonRpcResponseTest extends TestCase
{
    public function testSuccessResponse(): void
    {
        $response = JsonRpcResponse::success(1, ['task_id' => 'abc']);
        $array = $response->toArray();

        $this->assertSame('2.0', $array['jsonrpc']);
        $this->assertSame(1, $array['id']);
        $this->assertSame(['task_id' => 'abc'], $array['result']);
        $this->assertArrayNotHasKey('error', $array);
    }

    public function testErrorResponse(): void
    {
        $error = JsonRpcError::methodNotFound('foo.bar');
        $response = JsonRpcResponse::error(2, $error);
        $array = $response->toArray();

        $this->assertSame(2, $array['id']);
        $this->assertSame(-32601, $array['error']['code']);
        $this->assertStringContainsString('foo.bar', $array['error']['message']);
        $this->assertArrayNotHasKey('result', $array);
    }

    public function testToJson(): void
    {
        $response = JsonRpcResponse::success(1, 'ok');
        $json = $response->toJson();
        $decoded = json_decode($json, true);

        $this->assertSame('2.0', $decoded['jsonrpc']);
        $this->assertSame('ok', $decoded['result']);
    }
}
