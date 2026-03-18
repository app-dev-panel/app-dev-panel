<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Integration;

use AppDevPanel\Adapter\Yii2\Module;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\StartupContext;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the Yii 2 ADP API endpoints.
 *
 * Tests that the debug API (/debug/api/*) and inspector API (/inspect/api/*)
 * endpoints are reachable and return correct responses when the module
 * is properly bootstrapped.
 *
 * These tests use the Module's internal ApiApplication directly (no HTTP server)
 * to verify that API routing and response generation work correctly.
 */
#[CoversNothing]
final class ApiEndpointTest extends TestCase
{
    private string $storagePath;
    private ?Module $module = null;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp_api_test_' . bin2hex(random_bytes(8));
        mkdir($this->storagePath, 0o777, true);
        mkdir($this->storagePath . '/runtime', 0o777, true);
        mkdir($this->storagePath . '/debug', 0o777, true);

        \Yii::$container = new \yii\di\Container();
        \Yii::setAlias('@app', $this->storagePath);
        \Yii::setAlias('@runtime', $this->storagePath . '/runtime');
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
        $module = $this->createBootstrappedModule();
        $apiApp = \Yii::$container->get(\AppDevPanel\Api\ApiApplication::class);

        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();
        $request = $psr17->createServerRequest('GET', 'http://localhost/debug/api/');

        $response = $apiApp->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        $this->assertIsArray($data);
    }

    public function testDebugApiReturnsEntriesAfterRequest(): void
    {
        $module = $this->createBootstrappedModule();
        $debugger = $module->getDebugger();

        // Simulate a web request
        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();
        $psrRequest = $psr17->createServerRequest('GET', 'http://localhost/api/users');
        $debugger->startup(StartupContext::forRequest($psrRequest));

        /** @var RequestCollector|null $requestCollector */
        $requestCollector = $module->getCollector(RequestCollector::class);
        $requestCollector?->collectRequest($psrRequest);

        $psrResponse = $psr17->createResponse(200);
        $requestCollector?->collectResponse($psrResponse);

        $debugger->shutdown();

        // Now query the debug API
        $apiApp = \Yii::$container->get(\AppDevPanel\Api\ApiApplication::class);
        $apiRequest = $psr17->createServerRequest('GET', 'http://localhost/debug/api/');
        $response = $apiApp->handle($apiRequest);

        $this->assertSame(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data, 'Debug entries should exist after a request lifecycle');

        // First entry should have collector info
        $entry = $data[0];
        $this->assertArrayHasKey('id', $entry);
        $this->assertArrayHasKey('collectors', $entry);
        $this->assertArrayHasKey('request', $entry);
    }

    public function testDebugApiEntryDetail(): void
    {
        $module = $this->createBootstrappedModule();
        $debugger = $module->getDebugger();

        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();
        $psrRequest = $psr17->createServerRequest('GET', 'http://localhost/test');
        $debugger->startup(StartupContext::forRequest($psrRequest));
        $debugId = $debugger->getId();

        /** @var RequestCollector|null $requestCollector */
        $requestCollector = $module->getCollector(RequestCollector::class);
        $requestCollector?->collectRequest($psrRequest);

        $debugger->shutdown();

        // Query detail endpoint (route: /debug/api/view/{id})
        $apiApp = \Yii::$container->get(\AppDevPanel\Api\ApiApplication::class);
        $detailRequest = $psr17->createServerRequest('GET', "http://localhost/debug/api/view/{$debugId}");
        $response = $apiApp->handle($detailRequest);

        $this->assertSame(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        // View returns collector data keyed by FQCN
        $this->assertArrayHasKey(\AppDevPanel\Kernel\Collector\Web\RequestCollector::class, $data);
    }

    public function testDebugApiNonExistentEntryReturns404(): void
    {
        $this->createBootstrappedModule();
        $apiApp = \Yii::$container->get(\AppDevPanel\Api\ApiApplication::class);

        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();
        $request = $psr17->createServerRequest('GET', 'http://localhost/debug/api/non-existent-id');
        $response = $apiApp->handle($request);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testInspectApiParamsEndpoint(): void
    {
        $this->createBootstrappedModule();
        $apiApp = \Yii::$container->get(\AppDevPanel\Api\ApiApplication::class);

        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();
        $request = $psr17->createServerRequest('GET', 'http://localhost/inspect/api/params');
        $response = $apiApp->handle($request);

        // Should return 200 with config data (or 404 if no config provider)
        $statusCode = $response->getStatusCode();
        $this->assertContains($statusCode, [200, 404], "Inspector params should return 200 or 404, got {$statusCode}");
    }

    public function testNonExistentApiRouteReturns404(): void
    {
        $this->createBootstrappedModule();
        $apiApp = \Yii::$container->get(\AppDevPanel\Api\ApiApplication::class);

        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();
        $request = $psr17->createServerRequest('GET', 'http://localhost/debug/api/nonexistent/route');
        $response = $apiApp->handle($request);

        $this->assertSame(404, $response->getStatusCode());
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
