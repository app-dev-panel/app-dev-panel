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
}
