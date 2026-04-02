<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Mcp\Controller;

use AppDevPanel\Api\Http\JsonResponseFactory;
use AppDevPanel\Api\Mcp\Controller\McpSettingsController;
use AppDevPanel\Api\Mcp\McpSettings;
use GuzzleHttp\Psr7\HttpFactory;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;

final class McpSettingsControllerTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/adp-mcp-ctrl-test-' . uniqid();
        mkdir($this->tmpDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpDir . '/mcp-settings.json');
        @rmdir($this->tmpDir);
    }

    private function createController(): McpSettingsController
    {
        $httpFactory = new HttpFactory();

        return new McpSettingsController(
            new JsonResponseFactory($httpFactory, $httpFactory),
            new McpSettings($this->tmpDir),
        );
    }

    public function testIndexReturnsEnabled(): void
    {
        $controller = $this->createController();
        $response = $controller->index(new ServerRequest('GET', '/'));

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['enabled']);
    }

    public function testUpdateEnablesFalse(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('PUT', '/');
        $request = $request->withBody(Stream::create(json_encode(['enabled' => false])));

        $response = $controller->update($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($data['enabled']);
    }

    public function testUpdateEnablesTrue(): void
    {
        $controller = $this->createController();

        // First disable
        $request = new ServerRequest('PUT', '/');
        $request = $request->withBody(Stream::create(json_encode(['enabled' => false])));
        $controller->update($request);

        // Then enable
        $request2 = new ServerRequest('PUT', '/');
        $request2 = $request2->withBody(Stream::create(json_encode(['enabled' => true])));
        $response = $controller->update($request2);

        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['enabled']);
    }

    public function testUpdateMissingEnabledFieldReturns400(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('PUT', '/');
        $request = $request->withBody(Stream::create(json_encode(['other' => 'value'])));

        $response = $controller->update($request);

        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('enabled', $data['error']);
    }

    public function testUpdateInvalidBodyReturns400(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('PUT', '/');
        $request = $request->withBody(Stream::create('not json'));

        $response = $controller->update($request);

        $this->assertSame(400, $response->getStatusCode());
    }
}
