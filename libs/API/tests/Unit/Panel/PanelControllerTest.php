<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Panel;

use AppDevPanel\Api\Panel\PanelConfig;
use AppDevPanel\Api\Panel\PanelController;
use GuzzleHttp\Psr7\HttpFactory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class PanelControllerTest extends TestCase
{
    public function testIndexReturnsHtmlResponse(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $response = $controller->index($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testIndexContainsBundleJsScript(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $response = $controller->index($request);
        $body = (string) $response->getBody();

        $this->assertStringContainsString('bundle.js', $body);
        $this->assertStringContainsString('bundle.css', $body);
    }

    public function testIndexInjectsBackendUrl(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $response = $controller->index($request);
        $body = (string) $response->getBody();

        $this->assertStringContainsString('http://localhost:8080', $body);
        $this->assertStringContainsString('baseUrl', $body);
    }

    public function testIndexInjectsRouterBasename(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $response = $controller->index($request);
        $body = (string) $response->getBody();

        $this->assertStringContainsString("basename: '/debug'", $body);
        $this->assertStringContainsString('useHashRouter: false', $body);
    }

    public function testIndexUsesDefaultStaticUrl(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $response = $controller->index($request);
        $body = (string) $response->getBody();

        $this->assertStringContainsString(PanelConfig::DEFAULT_STATIC_URL, $body);
    }

    public function testIndexUsesCustomStaticUrl(): void
    {
        $config = new PanelConfig(staticUrl: 'https://cdn.example.com/panel');
        $controller = $this->createController($config);
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $response = $controller->index($request);
        $body = (string) $response->getBody();

        $this->assertStringContainsString('https://cdn.example.com/panel/bundle.js', $body);
        $this->assertStringContainsString('https://cdn.example.com/panel/bundle.css', $body);
    }

    public function testIndexUsesCustomViewerBasePath(): void
    {
        $config = new PanelConfig(viewerBasePath: '/app-debug');
        $controller = $this->createController($config);
        $request = new ServerRequest('GET', 'http://localhost:8080/app-debug');

        $response = $controller->index($request);
        $body = (string) $response->getBody();

        $this->assertStringContainsString("basename: '/app-debug'", $body);
    }

    public function testIndexContainsAppDevPanelWidgetConfig(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $response = $controller->index($request);
        $body = (string) $response->getBody();

        $this->assertStringContainsString("window['AppDevPanelWidget']", $body);
        $this->assertStringContainsString('containerId', $body);
        $this->assertStringContainsString('root', $body);
    }

    public function testIndexWorksForSubPaths(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', 'http://localhost:8080/debug/logs/detail');

        $response = $controller->index($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testViteDevServerLoadsHmrClient(): void
    {
        $config = new PanelConfig(staticUrl: 'http://localhost:3000');
        $controller = $this->createController($config);
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $response = $controller->index($request);
        $body = (string) $response->getBody();

        $this->assertStringContainsString('http://localhost:3000/@vite/client', $body);
        $this->assertStringContainsString('http://localhost:3000/src/index.tsx', $body);
        $this->assertStringNotContainsString('bundle.js', $body);
        $this->assertStringNotContainsString('bundle.css', $body);
    }

    public function testViteDevServerStillInjectsConfig(): void
    {
        $config = new PanelConfig(staticUrl: 'http://localhost:3000');
        $controller = $this->createController($config);
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $response = $controller->index($request);
        $body = (string) $response->getBody();

        $this->assertStringContainsString("window['AppDevPanelWidget']", $body);
        $this->assertStringContainsString("baseUrl: 'http://localhost:8080'", $body);
        $this->assertStringContainsString("basename: '/debug'", $body);
    }

    public function testProductionModeDoesNotLoadViteClient(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $response = $controller->index($request);
        $body = (string) $response->getBody();

        $this->assertStringNotContainsString('@vite/client', $body);
        $this->assertStringNotContainsString('src/index.tsx', $body);
        $this->assertStringContainsString('bundle.js', $body);
    }

    private function createController(?PanelConfig $config = null): PanelController
    {
        $factory = new HttpFactory();

        return new PanelController($factory, $factory, $config ?? new PanelConfig());
    }
}
