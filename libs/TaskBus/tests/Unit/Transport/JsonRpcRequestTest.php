<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Tests\Unit\Transport;

use AppDevPanel\TaskBus\Transport\JsonRpcRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonRpcRequest::class)]
final class JsonRpcRequestTest extends TestCase
{
    public function testFromJsonSingle(): void
    {
        $request = JsonRpcRequest::fromJson(
            '{"jsonrpc":"2.0","method":"task.submit","params":{"type":"run_command"},"id":1}',
        );
        $this->assertInstanceOf(JsonRpcRequest::class, $request);
        $this->assertSame('task.submit', $request->method);
        $this->assertSame(['type' => 'run_command'], $request->params);
        $this->assertSame(1, $request->id);
        $this->assertFalse($request->isNotification());
    }

    public function testFromJsonNotification(): void
    {
        $request = JsonRpcRequest::fromJson('{"jsonrpc":"2.0","method":"task.submit","params":{}}');
        $this->assertTrue($request->isNotification());
    }

    public function testFromJsonBatch(): void
    {
        $requests = JsonRpcRequest::fromJson('[{"method":"a","id":1},{"method":"b","id":2}]');
        $this->assertIsArray($requests);
        $this->assertCount(2, $requests);
        $this->assertSame('a', $requests[0]->method);
        $this->assertSame('b', $requests[1]->method);
    }

    public function testFromJsonInvalid(): void
    {
        $this->expectException(\JsonException::class);
        JsonRpcRequest::fromJson('not json');
    }
}
