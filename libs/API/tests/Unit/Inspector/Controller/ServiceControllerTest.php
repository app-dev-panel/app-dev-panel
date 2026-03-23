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
        mkdir($this->storagePath, 0o755, true);
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
        $response = $controller->list($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertSame('svc-alpha', $data[0]['service']);
        $this->assertTrue($data[0]['online']);
    }

    public function testListEmpty(): void
    {
        $controller = $this->createController();
        $response = $controller->list($this->get());

        $data = $this->responseData($response);
        $this->assertSame([], $data);
    }

    public function testDeregister(): void
    {
        $controller = $this->createControllerWithService('to-remove');
        $request = $this->get();
        $request = $request->withAttribute('service', 'to-remove');

        $response = $controller->deregister($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertTrue($data['deregistered']);

        // Verify it's gone
        $listResponse = $controller->list($this->get());
        $listData = $this->responseData($listResponse);
        $this->assertSame([], $listData);
    }

    public function testDeregisterEmptyService(): void
    {
        $controller = $this->createController();
        $request = $this->get();
        $request = $request->withAttribute('service', '');

        $this->expectException(InvalidArgumentException::class);
        $controller->deregister($request);
    }

    public function testDeregisterLocalReserved(): void
    {
        $controller = $this->createController();
        $request = $this->get();
        $request = $request->withAttribute('service', 'local');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('local');
        $controller->deregister($request);
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
