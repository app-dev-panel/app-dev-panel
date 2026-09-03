<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Panel;

use AppDevPanel\Api\Panel\PanelHtml;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PanelHtmlTest extends TestCase
{
    #[DataProvider('provideMounts')]
    public function testBaseHrefNormalisesMount(string $mount, string $expected): void
    {
        $this->assertSame($expected, PanelHtml::baseHref($mount));
    }

    public static function provideMounts(): iterable
    {
        yield 'default mount' => ['/debug', '/debug/'];
        yield 'trailing slash' => ['/debug/', '/debug/'];
        yield 'no leading slash' => ['debug', '/debug/'];
        yield 'nested mount' => ['/tools/adp', '/tools/adp/'];
        yield 'root' => ['/', '/'];
        yield 'empty' => ['', '/'];
    }

    public function testBaseTagCarriesMarkerAndEscapes(): void
    {
        $tag = PanelHtml::baseTag('/a"b');

        $this->assertSame('<base href="/a&quot;b/" data-adp-base />', $tag);
    }

    public function testInjectBaseHrefReplacesPlaceholder(): void
    {
        $html = '<!doctype html><html><head><meta charset="utf-8" /><base href="./" data-adp-base /><title>x</title></head><body></body></html>';

        $result = PanelHtml::injectBaseHref($html, '/debug');

        $this->assertStringContainsString('<base href="/debug/" data-adp-base />', $result);
        $this->assertStringNotContainsString('href="./"', $result);
        $this->assertSame(1, substr_count($result, '<base'));
    }

    public function testInjectBaseHrefInsertsAfterHeadWhenMissing(): void
    {
        $html = '<html><head><title>x</title></head><body></body></html>';

        $result = PanelHtml::injectBaseHref($html, '/');

        $this->assertStringStartsWith('<html><head><base href="/" data-adp-base /><title>', $result);
    }

    public function testInjectBaseHrefLeavesDocumentWithoutHeadUntouched(): void
    {
        $html = '<div>no head</div>';

        $this->assertSame($html, PanelHtml::injectBaseHref($html, '/debug'));
    }

    #[DataProvider('provideStaticUrls')]
    public function testResolveStaticUrl(string $staticUrl, string $mount, string $expected): void
    {
        $this->assertSame($expected, PanelHtml::resolveStaticUrl($staticUrl, $mount));
    }

    public static function provideStaticUrls(): iterable
    {
        yield 'absolute http' => ['https://cdn.example.com/panel/', '/debug', 'https://cdn.example.com/panel'];
        yield 'protocol relative' => ['//cdn.example.com/panel', '/debug', '//cdn.example.com/panel'];
        yield 'root absolute' => ['/bundles/appdevpanel/', '/debug', '/bundles/appdevpanel'];
        yield 'root' => ['/', '/debug', ''];
        yield 'dot at mount' => ['.', '/debug', '/debug'];
        yield 'dot slash at mount' => ['./', '/debug', '/debug'];
        yield 'dot slash at root mount' => ['./', '/', ''];
        yield 'relative dir' => ['./assets', '/debug', '/debug/assets'];
        yield 'bare relative dir' => ['assets/', '/tools/adp', '/tools/adp/assets'];
    }
}
