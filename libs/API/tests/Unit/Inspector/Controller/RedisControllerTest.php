<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\RedisController;
use RuntimeException;

final class RedisControllerTest extends ControllerTestCase
{
    private function createController(array $services = []): RedisController
    {
        return new RedisController($this->createResponseFactory(), $this->container($services));
    }

    public function testPingNoRedisService(): void
    {
        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not available');
        $controller->ping($this->get());
    }

    public function testInfoNoRedisService(): void
    {
        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not available');
        $controller->info($this->get());
    }

    public function testDbSizeNoRedisService(): void
    {
        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not available');
        $controller->dbSize($this->get());
    }

    public function testKeysNoRedisService(): void
    {
        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not available');
        $controller->keys($this->get());
    }

    public function testGetEmptyKey(): void
    {
        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not be empty');
        $controller->get($this->get(['key' => '']));
    }

    public function testGetNoRedisService(): void
    {
        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not available');
        $controller->get($this->get(['key' => 'test']));
    }

    public function testDeleteEmptyKey(): void
    {
        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not be empty');
        $controller->delete($this->get(['key' => '']));
    }

    public function testDeleteNoRedisService(): void
    {
        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not available');
        $controller->delete($this->get(['key' => 'test']));
    }

    public function testFlushDbNoRedisService(): void
    {
        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not available');
        $controller->flushDb($this->get());
    }

    public function testGetMissingKeyParameter(): void
    {
        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not be empty');
        $controller->get($this->get());
    }

    public function testDeleteMissingKeyParameter(): void
    {
        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not be empty');
        $controller->delete($this->get());
    }

    public function testPingSuccess(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('ping')->willReturn('+PONG');

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->ping($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('+PONG', $data['result']);
    }

    public function testPingException(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('ping')->willThrowException(new \RedisException('Connection refused'));

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->ping($this->get());

        $this->assertSame(500, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('Connection refused', $data['error']);
    }

    public function testInfoSuccess(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('info')->willReturn(['redis_version' => '7.0.0']);

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->info($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('7.0.0', $data['redis_version']);
    }

    public function testInfoWithSection(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->expects($this->once())->method('info')->with('memory')->willReturn(['used_memory' => '1024']);

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->info($this->get(['section' => 'memory']));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testInfoException(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('info')->willThrowException(new \RedisException('Timeout'));

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->info($this->get());

        $this->assertSame(500, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('Timeout', $data['error']);
    }

    public function testDbSizeSuccess(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('dbSize')->willReturn(42);

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->dbSize($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame(42, $data['size']);
    }

    public function testDbSizeException(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('dbSize')->willThrowException(new \RedisException('Err'));

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->dbSize($this->get());

        $this->assertSame(500, $response->getStatusCode());
    }

    public function testKeysExceptionFromScan(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('scan')->willThrowException(new \RedisException('Err'));

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->keys($this->get());

        $this->assertSame(500, $response->getStatusCode());
    }

    public function testGetStringValue(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('type')->willReturn(\Redis::REDIS_STRING);
        $redis->method('ttl')->willReturn(300);
        $redis->method('get')->willReturn('hello');

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->get($this->get(['key' => 'mykey']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('mykey', $data['key']);
        $this->assertSame('string', $data['type']);
        $this->assertSame(300, $data['ttl']);
        $this->assertSame('hello', $data['value']);
    }

    public function testGetListValue(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('type')->willReturn(\Redis::REDIS_LIST);
        $redis->method('ttl')->willReturn(-1);
        $redis->method('lRange')->willReturn(['a', 'b', 'c']);

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->get($this->get(['key' => 'mylist']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('list', $data['type']);
        $this->assertSame(['a', 'b', 'c'], $data['value']);
    }

    public function testGetSetValue(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('type')->willReturn(\Redis::REDIS_SET);
        $redis->method('ttl')->willReturn(-1);
        $redis->method('sMembers')->willReturn(['x', 'y']);

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->get($this->get(['key' => 'myset']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('set', $data['type']);
    }

    public function testGetZsetValue(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('type')->willReturn(\Redis::REDIS_ZSET);
        $redis->method('ttl')->willReturn(-1);
        $redis->method('zRange')->willReturn(['a' => 1.0, 'b' => 2.0]);

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->get($this->get(['key' => 'myzset']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('zset', $data['type']);
    }

    public function testGetHashValue(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('type')->willReturn(\Redis::REDIS_HASH);
        $redis->method('ttl')->willReturn(-1);
        $redis->method('hGetAll')->willReturn(['field1' => 'val1']);

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->get($this->get(['key' => 'myhash']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('hash', $data['type']);
        $this->assertSame(['field1' => 'val1'], $data['value']);
    }

    public function testGetStreamValue(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('type')->willReturn(\Redis::REDIS_STREAM);
        $redis->method('ttl')->willReturn(-1);
        $redis->method('xRange')->willReturn([]);

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->get($this->get(['key' => 'mystream']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('stream', $data['type']);
    }

    public function testGetNotFoundType(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('type')->willReturn(\Redis::REDIS_NOT_FOUND);
        $redis->method('ttl')->willReturn(-2);

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->get($this->get(['key' => 'gone']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('none', $data['type']);
        $this->assertNull($data['value']);
    }

    public function testGetException(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('type')->willThrowException(new \RedisException('Get failed'));

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->get($this->get(['key' => 'mykey']));

        $this->assertSame(500, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('Get failed', $data['error']);
    }

    public function testDeleteSuccess(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('del')->willReturn(1);

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->delete($this->get(['key' => 'mykey']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame(1, $data['result']);
    }

    public function testDeleteException(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('del')->willThrowException(new \RedisException('Del failed'));

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->delete($this->get(['key' => 'mykey']));

        $this->assertSame(500, $response->getStatusCode());
    }

    public function testFlushDbSuccess(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('flushDB')->willReturn(true);

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->flushDb($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertTrue($data['result']);
    }

    public function testFlushDbException(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available.');
        }

        $redis = $this->createMock(\Redis::class);
        $redis->method('flushDB')->willThrowException(new \RedisException('Flush failed'));

        $controller = $this->createController([\Redis::class => $redis]);
        $response = $controller->flushDb($this->get());

        $this->assertSame(500, $response->getStatusCode());
    }
}
