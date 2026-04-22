<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Toolbar;

use AppDevPanel\Api\Panel\PanelConfig;
use AppDevPanel\Api\Toolbar\ToolbarConfig;
use AppDevPanel\Api\Toolbar\ToolbarInjector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ToolbarInjectorTest extends TestCase
{
    public function testIsEnabledReturnsTrue(): void
    {
        $injector = new ToolbarInjector(new PanelConfig());

        $this->assertTrue($injector->isEnabled());
    }

    public function testIsEnabledReturnsFalseWhenDisabled(): void
    {
        $injector = new ToolbarInjector(new PanelConfig(), new ToolbarConfig(enabled: false));

        $this->assertFalse($injector->isEnabled());
    }

    #[DataProvider('htmlContentTypeProvider')]
    public function testIsHtmlResponseDetectsHtml(string $contentType, bool $expected): void
    {
        $injector = new ToolbarInjector(new PanelConfig());

        $this->assertSame($expected, $injector->isHtmlResponse($contentType));
    }

    public static function htmlContentTypeProvider(): iterable
    {
        yield 'text/html' => ['text/html', true];
        yield 'text/html with charset' => ['text/html; charset=utf-8', true];
        yield 'application/json' => ['application/json', false];
        yield 'text/plain' => ['text/plain', false];
        yield 'empty' => ['', false];
    }

    public function testInjectAddsToolbarBeforeBodyClose(): void
    {
        $injector = new ToolbarInjector(new PanelConfig(staticUrl: '/assets'));

        $html = '<html><body><h1>Hello</h1></body></html>';
        $result = $injector->inject($html, 'http://localhost:8000', 'debug-123');

        $this->assertStringContainsString('<div id="app-dev-toolbar"', $result);
        $this->assertStringContainsString('/assets/toolbar/bundle.js', $result);
        $this->assertStringContainsString('/assets/toolbar/bundle.css', $result);
        $this->assertStringContainsString("baseUrl: 'http://localhost:8000'", $result);
        $this->assertStringContainsString("debugId: 'debug-123'", $result);
        $this->assertStringContainsString('</body></html>', $result);
    }

    public function testInjectPreservesHtmlStructure(): void
    {
        $injector = new ToolbarInjector(new PanelConfig(staticUrl: '/assets'));

        $html = '<!doctype html><html><head><title>Test</title></head><body><p>Content</p></body></html>';
        $result = $injector->inject($html, 'http://localhost', '');

        // Toolbar div should appear before </body>
        $toolbarPos = strpos($result, '<div id="app-dev-toolbar"');
        $bodyClosePos = strpos($result, '</body>');
        $this->assertNotFalse($toolbarPos);
        $this->assertNotFalse($bodyClosePos);
        $this->assertLessThan($bodyClosePos, $toolbarPos);
    }

    public function testInjectReturnsUnchangedWhenNoBodyTag(): void
    {
        $injector = new ToolbarInjector(new PanelConfig());

        $html = '<div>No body tag here</div>';
        $result = $injector->inject($html, 'http://localhost', '');

        $this->assertSame($html, $result);
    }

    public function testInjectUsesToolbarStaticUrlWhenSet(): void
    {
        $injector = new ToolbarInjector(
            new PanelConfig(staticUrl: '/panel'),
            new ToolbarConfig(staticUrl: 'http://localhost:3001'),
        );

        $html = '<html><body></body></html>';
        $result = $injector->inject($html, 'http://localhost', '');

        $this->assertStringContainsString('http://localhost:3001/toolbar/bundle.js', $result);
        $this->assertStringNotContainsString('/panel/', $result);
    }

    public function testInjectFallsToPanelStaticUrlWhenToolbarUrlEmpty(): void
    {
        $injector = new ToolbarInjector(
            new PanelConfig(staticUrl: '/bundles/appdevpanel'),
            new ToolbarConfig(staticUrl: ''),
        );

        $html = '<html><body></body></html>';
        $result = $injector->inject($html, 'http://localhost', '');

        $this->assertStringContainsString('/bundles/appdevpanel/toolbar/bundle.js', $result);
    }

    public function testInjectEscapesStaticUrl(): void
    {
        $injector = new ToolbarInjector(new PanelConfig(staticUrl: '/path"><script>alert(1)</script>'));

        $html = '<html><body></body></html>';
        $result = $injector->inject($html, 'http://localhost', '');

        $this->assertStringNotContainsString('<script>alert(1)</script>', $result);
        $this->assertStringContainsString('&quot;', $result);
    }

    public function testInjectHandlesCaseInsensitiveBodyTag(): void
    {
        $injector = new ToolbarInjector(new PanelConfig(staticUrl: '/assets'));

        $html = '<html><BODY><h1>Hello</h1></BODY></html>';
        $result = $injector->inject($html, 'http://localhost', '');

        $this->assertStringContainsString('<div id="app-dev-toolbar"', $result);
    }

    public function testIsPanelRequestMatchesViewerBasePath(): void
    {
        $injector = new ToolbarInjector(new PanelConfig(viewerBasePath: '/debug'));

        $this->assertTrue($injector->isPanelRequest('/debug'));
        $this->assertTrue($injector->isPanelRequest('/debug/'));
        $this->assertTrue($injector->isPanelRequest('/debug/inspector/routes'));
        $this->assertTrue($injector->isPanelRequest('/debug/api/summary/1'));
    }

    public function testIsPanelRequestRejectsUnrelatedPaths(): void
    {
        $injector = new ToolbarInjector(new PanelConfig(viewerBasePath: '/debug'));

        $this->assertFalse($injector->isPanelRequest('/'));
        $this->assertFalse($injector->isPanelRequest('/debugger'));
        $this->assertFalse($injector->isPanelRequest('/users/debug'));
        $this->assertFalse($injector->isPanelRequest('/api/debug'));
    }

    public function testIsPanelRequestHonoursCustomViewerBasePath(): void
    {
        $injector = new ToolbarInjector(new PanelConfig(viewerBasePath: '/_adp/'));

        $this->assertTrue($injector->isPanelRequest('/_adp'));
        $this->assertTrue($injector->isPanelRequest('/_adp/inspector'));
        $this->assertFalse($injector->isPanelRequest('/debug'));
    }

    public function testIsPanelRequestReturnsFalseForEmptyBasePath(): void
    {
        $injector = new ToolbarInjector(new PanelConfig(viewerBasePath: ''));

        $this->assertFalse($injector->isPanelRequest('/'));
        $this->assertFalse($injector->isPanelRequest('/debug'));
    }

    public function testInjectTrailingSlashHandling(): void
    {
        $injector = new ToolbarInjector(new PanelConfig(staticUrl: '/assets/'));

        $html = '<html><body></body></html>';
        $result = $injector->inject($html, 'http://localhost', '');

        // Should not produce double slashes
        $this->assertStringNotContainsString('//toolbar/', $result);
        $this->assertStringContainsString('/assets/toolbar/bundle.js', $result);
    }
}
