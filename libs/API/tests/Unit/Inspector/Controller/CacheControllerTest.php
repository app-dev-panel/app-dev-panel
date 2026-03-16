<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\CacheController;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

final class CacheControllerTest extends ControllerTestCase
{
    private function createController(): CacheController
    {
        return new CacheController($this->createResponseFactory());
    }

    public function testViewSuccess(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('has')->with('my-key')->willReturn(true);
        $cache->method('get')->with('my-key')->willReturn(['cached' => 'data']);

        $container = $this->container([CacheInterface::class => $cache]);

        $controller = $this->createController();
        $response = $controller->view($this->get(['key' => 'my-key']), $container);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testViewKeyNotFound(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('has')->with('missing')->willReturn(false);

        $container = $this->container([CacheInterface::class => $cache]);

        $controller = $this->createController();
        $response = $controller->view($this->get(['key' => 'missing']), $container);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testViewEmptyKey(): void
    {
        $container = $this->container();

        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not be empty');
        $controller->view($this->get(['key' => '']), $container);
    }

    public function testViewNoCacheService(): void
    {
        $container = $this->container();

        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not available');
        $controller->view($this->get(['key' => 'test']), $container);
    }

    public function testDeleteSuccess(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('has')->with('my-key')->willReturn(true);
        $cache->method('delete')->with('my-key')->willReturn(true);

        $container = $this->container([CacheInterface::class => $cache]);

        $controller = $this->createController();
        $response = $controller->delete($this->get(['key' => 'my-key']), $container);

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertTrue($data['result']);
    }

    public function testDeleteKeyNotFound(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('has')->with('missing')->willReturn(false);

        $container = $this->container([CacheInterface::class => $cache]);

        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');
        $controller->delete($this->get(['key' => 'missing']), $container);
    }

    public function testDeleteEmptyKey(): void
    {
        $container = $this->container();

        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $controller->delete($this->get(), $container);
    }

    public function testDeleteNoCacheService(): void
    {
        $container = $this->container();

        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not available');
        $controller->delete($this->get(['key' => 'test']), $container);
    }

    public function testClearSuccess(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('clear')->willReturn(true);

        $container = $this->container([CacheInterface::class => $cache]);

        $controller = $this->createController();
        $response = $controller->clear($container);

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertTrue($data['result']);
    }

    public function testClearNoCacheService(): void
    {
        $container = $this->container();

        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $controller->clear($container);
    }
}
