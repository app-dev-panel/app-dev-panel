<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\CacheController;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

final class CacheControllerTest extends ControllerTestCase
{
    private function createController(array $services = []): CacheController
    {
        return new CacheController($this->createResponseFactory(), $this->container($services));
    }

    public function testViewSuccess(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('has')->with('my-key')->willReturn(true);
        $cache->method('get')->with('my-key')->willReturn(['cached' => 'data']);

        $controller = $this->createController([CacheInterface::class => $cache]);
        $response = $controller->view($this->get(['key' => 'my-key']));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testViewKeyNotFound(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('has')->with('missing')->willReturn(false);

        $controller = $this->createController([CacheInterface::class => $cache]);
        $response = $controller->view($this->get(['key' => 'missing']));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testViewEmptyKey(): void
    {
        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not be empty');
        $controller->view($this->get(['key' => '']));
    }

    public function testViewNoCacheService(): void
    {
        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not available');
        $controller->view($this->get(['key' => 'test']));
    }

    public function testDeleteSuccess(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('has')->with('my-key')->willReturn(true);
        $cache->method('delete')->with('my-key')->willReturn(true);

        $controller = $this->createController([CacheInterface::class => $cache]);
        $response = $controller->delete($this->get(['key' => 'my-key']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertTrue($data['result']);
    }

    public function testDeleteKeyNotFound(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('has')->with('missing')->willReturn(false);

        $controller = $this->createController([CacheInterface::class => $cache]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');
        $controller->delete($this->get(['key' => 'missing']));
    }

    public function testDeleteEmptyKey(): void
    {
        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $controller->delete($this->get());
    }

    public function testDeleteNoCacheService(): void
    {
        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not available');
        $controller->delete($this->get(['key' => 'test']));
    }

    public function testClearSuccess(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('clear')->willReturn(true);

        $controller = $this->createController([CacheInterface::class => $cache]);
        $response = $controller->clear($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertTrue($data['result']);
    }

    public function testClearNoCacheService(): void
    {
        $controller = $this->createController();

        $this->expectException(RuntimeException::class);
        $controller->clear($this->get());
    }
}
