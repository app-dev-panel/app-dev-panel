<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Panel;

use AppDevPanel\Api\Panel\PanelConfig;
use AppDevPanel\Api\Panel\PanelController;
use GuzzleHttp\Psr7\HttpFactory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $this->assertStringContainsString('charset=utf-8', $response->getHeaderLine('Content-Type'));
    }

    public function testIndexRendersValidHtmlDocument(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $body = $this->getBody($controller, $request);

        $this->assertStringContainsString('<!doctype html>', $body);
        $this->assertStringContainsString('<html lang="en">', $body);
        $this->assertStringContainsString('</html>', $body);
        $this->assertStringContainsString('<title>App Dev Panel</title>', $body);
    }

    public function testIndexLoadsBundleAssets(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $body = $this->getBody($controller, $request);

        $this->assertStringContainsString('bundle.js', $body);
        $this->assertStringContainsString('bundle.css', $body);
        $this->assertStringContainsString('type="module"', $body);
    }

    public function testIndexInjectsBackendUrlFromRequest(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', 'http://myapp.local:9090/debug');

        $body = $this->getBody($controller, $request);

        $this->assertStringContainsString("baseUrl: 'http://myapp.local:9090'", $body);
    }

    public function testIndexInjectsRouterBasename(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $body = $this->getBody($controller, $request);

        $this->assertStringContainsString("basename: '/debug'", $body);
        $this->assertStringContainsString('useHashRouter: false', $body);
    }

    public function testIndexInjectsWidgetConfig(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $body = $this->getBody($controller, $request);

        $this->assertStringContainsString("window['AppDevPanelWidget']", $body);
        $this->assertStringContainsString("containerId: 'root'", $body);
        $this->assertStringContainsString('usePreferredUrl: true', $body);
    }

    public function testIndexUsesDefaultStaticUrl(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $body = $this->getBody($controller, $request);

        $this->assertStringContainsString(PanelConfig::DEFAULT_STATIC_URL . '/bundle.js', $body);
        $this->assertStringContainsString(PanelConfig::DEFAULT_STATIC_URL . '/bundle.css', $body);
    }

    #[DataProvider('provideStaticUrls')]
    public function testIndexUsesConfiguredStaticUrl(string $staticUrl): void
    {
        $config = new PanelConfig(staticUrl: $staticUrl);
        $controller = $this->createController($config);
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $body = $this->getBody($controller, $request);

        $this->assertStringContainsString($staticUrl . '/bundle.js', $body);
        $this->assertStringContainsString($staticUrl . '/bundle.css', $body);
    }

    public static function provideStaticUrls(): iterable
    {
        yield 'symfony local assets' => ['/bundles/appdevpanel'];
        yield 'laravel local assets' => ['/vendor/app-dev-panel'];
        yield 'yii2 local assets' => ['/app-dev-panel'];
        yield 'custom CDN' => ['https://cdn.example.com/panel'];
    }

    public function testIndexUsesCustomViewerBasePath(): void
    {
        $config = new PanelConfig(viewerBasePath: '/app-debug');
        $controller = $this->createController($config);
        $request = new ServerRequest('GET', 'http://localhost:8080/app-debug');

        $body = $this->getBody($controller, $request);

        $this->assertStringContainsString("basename: '/app-debug'", $body);
    }

    public function testIndexTrimsTrailingSlashFromStaticUrl(): void
    {
        $config = new PanelConfig(staticUrl: '/bundles/appdevpanel/');
        $controller = $this->createController($config);
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $body = $this->getBody($controller, $request);

        $this->assertStringContainsString('/bundles/appdevpanel/bundle.js', $body);
        $this->assertStringNotContainsString('/bundles/appdevpanel//bundle.js', $body);
    }

    public function testIndexWorksForSubPaths(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', 'http://localhost:8080/debug/logs/detail/123');

        $response = $controller->index($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testIndexEscapesHtmlInStaticUrl(): void
    {
        $config = new PanelConfig(staticUrl: '/path"><script>alert(1)</script>');
        $controller = $this->createController($config);
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $body = $this->getBody($controller, $request);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $body);
        $this->assertStringContainsString('&gt;', $body);
    }

    public function testIndexContainsMountPoint(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $body = $this->getBody($controller, $request);

        $this->assertStringContainsString('<div id="root"', $body);
        $this->assertStringContainsString('<noscript>', $body);
    }

    public function testIndexContainsFavicon(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('GET', 'http://localhost:8080/debug');

        $body = $this->getBody($controller, $request);

        $this->assertStringContainsString('favicon.ico', $body);
    }

    private function createController(?PanelConfig $config = null): PanelController
    {
        $factory = new HttpFactory();

        return new PanelController($factory, $factory, $config ?? new PanelConfig());
    }

    private function getBody(PanelController $controller, ServerRequest $request): string
    {
        return (string) $controller->index($request)->getBody();
    }
}
