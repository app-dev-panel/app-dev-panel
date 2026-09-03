<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\ServiceController;
use AppDevPanel\Api\Security\UrlPolicy;
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

    /**
     * Stub DNS so the SSRF policy never touches the network: `python-app` is public,
     * `internal.corp` is RFC1918, everything else is unresolvable.
     */
    private function urlPolicy(bool $publicOnly = false): UrlPolicy
    {
        return new UrlPolicy($publicOnly, static fn(string $host): array => match ($host) {
            'python-app' => ['203.0.113.10'],
            'internal.corp' => ['10.0.0.5'],
            default => [],
        });
    }

    private function createController(bool $publicOnly = false): ServiceController
    {
        $registry = new FileServiceRegistry($this->storagePath);

        return new ServiceController($this->createResponseFactory(), $registry, $this->urlPolicy($publicOnly));
    }

    private function createControllerWithService(string $service = 'test-svc'): ServiceController
    {
        $registry = new FileServiceRegistry($this->storagePath);
        $now = microtime(true);
        $registry->register(
            new ServiceDescriptor($service, 'python', 'http://localhost:9090', ['config', 'routes'], $now, $now),
        );

        return new ServiceController($this->createResponseFactory(), $registry, $this->urlPolicy());
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

    /**
     * Refused in both policy modes.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function rejectedInspectorUrls(): iterable
    {
        yield 'cloud metadata' => ['http://169.254.169.254/latest/meta-data', 'link-local'];
        yield 'link local v6' => ['http://[fe80::1]:9090', 'link-local'];
        yield 'unspecified' => ['http://0.0.0.0:9090', 'link-local'];
        yield 'userinfo' => ['http://admin:pw@python-app:9090', 'credentials'];
        yield 'gopher scheme' => ['gopher://python-app:70', 'scheme'];
        yield 'javascript scheme' => ['javascript://python-app/%0aalert(1)', 'scheme'];
        yield 'file scheme' => ['file:///etc/passwd', 'malformed'];
        yield 'unresolvable' => ['http://nope.invalid', 'could not be resolved'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('rejectedInspectorUrls')]
    public function testRegisterRejectsUnsafeInspectorUrl(string $url, string $reason): void
    {
        foreach ([false, true] as $publicOnly) {
            $controller = $this->createController($publicOnly);

            try {
                $controller->register($this->post(['service' => 'evil', 'inspectorUrl' => $url]));
                $this->fail('Expected the inspectorUrl to be rejected.');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('inspectorUrl', $e->getMessage());
                $this->assertStringContainsString($reason, $e->getMessage());
            }

            // Nothing must have been persisted.
            $this->assertSame([], $this->responseData($controller->list($this->get())));
        }
    }

    /**
     * The normal localhost / docker-compose case: accepted by default.
     *
     * @return iterable<string, array{string}>
     */
    public static function privateInspectorUrls(): iterable
    {
        yield 'localhost' => ['http://localhost:9090'];
        yield 'loopback' => ['http://127.0.0.1:9090'];
        yield 'loopback v6' => ['http://[::1]:9090'];
        yield 'rfc1918 literal' => ['http://10.0.0.5:9090'];
        yield 'docker network name' => ['http://internal.corp:9090'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('privateInspectorUrls')]
    public function testRegisterAllowsPrivateInspectorUrlByDefault(string $url): void
    {
        $controller = $this->createController();
        $response = $controller->register($this->post(['service' => 'local-python', 'inspectorUrl' => $url]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($this->responseData($response)['registered']);
        $this->assertSame($url, $this->responseData($controller->list($this->get()))[0]['inspectorUrl']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('privateInspectorUrls')]
    public function testRegisterRejectsPrivateInspectorUrlInPublicHostsOnlyMode(string $url): void
    {
        $controller = $this->createController(publicOnly: true);

        try {
            $controller->register($this->post(['service' => 'local-python', 'inspectorUrl' => $url]));
            $this->fail('Expected the inspectorUrl to be rejected.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('inspectorUrl', $e->getMessage());
            $this->assertMatchesRegularExpression('/loopback|private/', $e->getMessage());
        }

        $this->assertSame([], $this->responseData($controller->list($this->get())));
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
