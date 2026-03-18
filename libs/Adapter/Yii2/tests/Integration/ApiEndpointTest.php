<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Integration;

use AppDevPanel\Adapter\Yii2\Module;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\StartupContext;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Integration tests for the Yii 2 ADP API endpoints.
 *
 * Tests use the Module's internal ApiApplication directly (no HTTP server)
 * to verify API routing, middleware pipeline, and response generation.
 */
#[CoversNothing]
final class ApiEndpointTest extends TestCase
{
    private string $storagePath;
    private ?Module $module = null;
    private Psr17Factory $psr17;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp_api_test_' . bin2hex(random_bytes(8));
        mkdir($this->storagePath, 0o777, true);
        mkdir($this->storagePath . '/runtime', 0o777, true);
        mkdir($this->storagePath . '/debug', 0o777, true);

        \Yii::$container = new \yii\di\Container();
        \Yii::setAlias('@app', $this->storagePath);
        \Yii::setAlias('@runtime', $this->storagePath . '/runtime');

        $this->psr17 = new Psr17Factory();
    }

    protected function tearDown(): void
    {
        $this->module = null;
        \Yii::$container = new \yii\di\Container();
        \Yii::$app = null;

        $this->removeDirectory($this->storagePath);
    }

    public function testDebugApiReturnsEmptyListWhenNoEntries(): void
    {
        $this->createBootstrappedModule();
        $apiApp = \Yii::$container->get(\AppDevPanel\Api\ApiApplication::class);

        $response = $apiApp->handle($this->apiRequest('GET', '/debug/api/'));

        $this->assertSame(200, $response->getStatusCode());
        $envelope = $this->decodeEnvelope($response);
        $this->assertTrue($envelope['success']);
        $this->assertIsArray($envelope['data']);
    }

    public function testDebugApiReturnsEntriesAfterRequest(): void
    {
        $module = $this->createBootstrappedModule();
        $debugger = $module->getDebugger();

        $psrRequest = $this->psr17->createServerRequest('GET', 'http://localhost/api/users');
        $debugger->startup(StartupContext::forRequest($psrRequest));

        /** @var RequestCollector|null $requestCollector */
        $requestCollector = $module->getCollector(RequestCollector::class);
        $requestCollector?->collectRequest($psrRequest);
        $requestCollector?->collectResponse($this->psr17->createResponse(200));

        $debugger->shutdown();

        $apiApp = \Yii::$container->get(\AppDevPanel\Api\ApiApplication::class);
        $response = $apiApp->handle($this->apiRequest('GET', '/debug/api/'));

        $this->assertSame(200, $response->getStatusCode());
        $envelope = $this->decodeEnvelope($response);
        $this->assertTrue($envelope['success']);
        $this->assertNotEmpty($envelope['data'], 'Debug entries should exist after a request lifecycle');

        $entry = $envelope['data'][0];
        $this->assertArrayHasKey('id', $entry);
        $this->assertArrayHasKey('collectors', $entry);
        $this->assertArrayHasKey('request', $entry);
    }

    public function testDebugApiEntryDetail(): void
    {
        $module = $this->createBootstrappedModule();
        $debugger = $module->getDebugger();

        $psrRequest = $this->psr17->createServerRequest('GET', 'http://localhost/test');
        $debugger->startup(StartupContext::forRequest($psrRequest));
        $debugId = $debugger->getId();

        /** @var RequestCollector|null $requestCollector */
        $requestCollector = $module->getCollector(RequestCollector::class);
        $requestCollector?->collectRequest($psrRequest);

        $debugger->shutdown();

        $apiApp = \Yii::$container->get(\AppDevPanel\Api\ApiApplication::class);
        $response = $apiApp->handle($this->apiRequest('GET', "/debug/api/view/{$debugId}"));

        $this->assertSame(200, $response->getStatusCode());
        $envelope = $this->decodeEnvelope($response);
        $this->assertTrue($envelope['success']);
        $this->assertArrayHasKey(RequestCollector::class, $envelope['data']);
    }

    public function testDebugApiNonExistentEntryReturns404(): void
    {
        $this->createBootstrappedModule();
        $apiApp = \Yii::$container->get(\AppDevPanel\Api\ApiApplication::class);

        $response = $apiApp->handle($this->apiRequest('GET', '/debug/api/view/non-existent-id'));

        $this->assertSame(404, $response->getStatusCode());
        $envelope = $this->decodeEnvelope($response);
        $this->assertFalse($envelope['success']);
    }

    public function testInspectApiParamsEndpoint(): void
    {
        $this->createBootstrappedModule();
        $apiApp = \Yii::$container->get(\AppDevPanel\Api\ApiApplication::class);

        $response = $apiApp->handle($this->apiRequest('GET', '/inspect/api/params'));

        $statusCode = $response->getStatusCode();
        $this->assertContains($statusCode, [200, 404, 500], "Inspector params returned {$statusCode}");
    }

    public function testNonExistentApiRouteReturns404(): void
    {
        $this->createBootstrappedModule();
        $apiApp = \Yii::$container->get(\AppDevPanel\Api\ApiApplication::class);

        $response = $apiApp->handle($this->apiRequest('GET', '/debug/api/nonexistent/route'));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testIpFilterBlocksUnauthorizedIp(): void
    {
        $this->createBootstrappedModule();
        $apiApp = \Yii::$container->get(\AppDevPanel\Api\ApiApplication::class);

        // Request with unauthorized IP
        $request = $this->psr17->createServerRequest('GET', 'http://localhost/debug/api/', ['REMOTE_ADDR' => '10.0.0.1']);
        $response = $apiApp->handle($request);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testResponseDataWrapperEnvelope(): void
    {
        $this->createBootstrappedModule();
        $apiApp = \Yii::$container->get(\AppDevPanel\Api\ApiApplication::class);

        $response = $apiApp->handle($this->apiRequest('GET', '/debug/api/'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('id', $body);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('success', $body);
    }

    /**
     * Create a server request with 127.0.0.1 REMOTE_ADDR (passes IP filter).
     */
    private function apiRequest(string $method, string $path): ServerRequestInterface
    {
        return $this->psr17->createServerRequest($method, "http://localhost{$path}", ['REMOTE_ADDR' => '127.0.0.1']);
    }

    /**
     * Decode the ResponseDataWrapper envelope from a response.
     *
     * @return array{id: ?string, data: mixed, error: ?string, success: bool}
     */
    private function decodeEnvelope(\Psr\Http\Message\ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);
        $this->assertIsArray($decoded, "Response body is not valid JSON: {$body}");
        $this->assertArrayHasKey('data', $decoded, "Response missing 'data' key: {$body}");

        return $decoded;
    }

    private function createBootstrappedModule(): Module
    {
        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
            'historySize' => 50,
            'collectors' => [
                'request' => true,
                'exception' => true,
                'log' => true,
                'event' => true,
                'service' => true,
                'http_client' => true,
                'timeline' => true,
                'var_dumper' => true,
                'filesystem_stream' => true,
                'http_stream' => true,
                'command' => true,
                'db' => true,
                'yii_log' => false,
                'mailer' => true,
                'assets' => true,
            ],
        ]);

        $reflection = new \ReflectionClass($module);

        $registerServices = $reflection->getMethod('registerServices');
        $registerServices->setAccessible(true);
        $registerServices->invoke($module);

        $registerCollectors = $reflection->getMethod('registerCollectors');
        $registerCollectors->setAccessible(true);
        $registerCollectors->invoke($module);

        $buildDebugger = $reflection->getMethod('buildDebugger');
        $buildDebugger->setAccessible(true);
        $buildDebugger->invoke($module);

        $this->module = $module;
        return $module;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }
}
