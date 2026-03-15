<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\ServiceController;
use AppDevPanel\Kernel\Service\FileServiceRegistry;
use AppDevPanel\Kernel\Service\ServiceDescriptor;
use InvalidArgumentException;

final class ServiceControllerTest extends ControllerTestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp-svc-ctrl-test-' . uniqid();
        mkdir($this->storagePath, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->storagePath);
    }

    private function createController(): ServiceController
    {
        $registry = new FileServiceRegistry($this->storagePath);

        return new ServiceController($this->createResponseFactory(), $registry);
    }

    private function createControllerWithService(string $service = 'test-svc'): ServiceController
    {
        $registry = new FileServiceRegistry($this->storagePath);
        $now = microtime(true);
        $registry->register(
            new ServiceDescriptor($service, 'python', 'http://localhost:9090', ['config', 'routes'], $now, $now),
        );

        return new ServiceController($this->createResponseFactory(), $registry);
    }

    public function testRegister(): void
    {
        $controller = $this->createController();
        $response = $controller->register($this->post([
            'service' => 'my-python-app',
            'language' => 'python',
            'inspectorUrl' => 'http://python-app:9090',
            'capabilities' => ['config', 'routes', 'files'],
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('my-python-app', $data['service']);
        $this->assertTrue($data['registered']);
    }

    public function testRegisterMissingService(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('service');
        $controller->register($this->post(['inspectorUrl' => 'http://localhost:9090']));
    }

    public function testRegisterEmptyService(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('service');
        $controller->register($this->post(['service' => '', 'inspectorUrl' => 'http://localhost:9090']));
    }

    public function testRegisterReservedLocal(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved');
        $controller->register($this->post(['service' => 'local', 'inspectorUrl' => 'http://localhost:9090']));
    }

    public function testRegisterMissingInspectorUrl(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('inspectorUrl');
        $controller->register($this->post(['service' => 'my-app']));
    }

    public function testHeartbeat(): void
    {
        $controller = $this->createControllerWithService();
        $response = $controller->heartbeat($this->post(['service' => 'test-svc']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertTrue($data['acknowledged']);
    }

    public function testHeartbeatUnknownService(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not registered');
        $controller->heartbeat($this->post(['service' => 'nonexistent']));
    }

    public function testHeartbeatMissingService(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('service');
        $controller->heartbeat($this->post([]));
    }

    public function testList(): void
    {
        $controller = $this->createControllerWithService('svc-alpha');
        $response = $controller->list();

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('services', $data);
        $this->assertCount(1, $data['services']);
        $this->assertSame('svc-alpha', $data['services'][0]['service']);
        $this->assertSame('online', $data['services'][0]['status']);
    }

    public function testListEmpty(): void
    {
        $controller = $this->createController();
        $response = $controller->list();

        $data = $this->responseData($response);
        $this->assertSame([], $data['services']);
    }

    public function testDeregister(): void
    {
        $controller = $this->createControllerWithService('to-remove');
        $route = $this->route(['service' => 'to-remove']);

        $response = $controller->deregister($this->get(), $route);

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertTrue($data['deregistered']);

        // Verify it's gone
        $listResponse = $controller->list();
        $listData = $this->responseData($listResponse);
        $this->assertSame([], $listData['services']);
    }

    public function testDeregisterEmptyService(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $controller->deregister($this->get(), $this->route(['service' => '']));
    }

    public function testDeregisterLocalReserved(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('local');
        $controller->deregister($this->get(), $this->route(['service' => 'local']));
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
