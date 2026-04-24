<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Controller;

use AppDevPanel\Adapter\Laravel\Controller\FrontendAssetsController;
use AppDevPanel\FrontendAssets\FrontendAssets;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

final class FrontendAssetsControllerTest extends TestCase
{
    private string $distPath;

    /** @var list<string> */
    private array $fixtureFiles = [];

    protected function setUp(): void
    {
        $this->distPath = FrontendAssets::path();

        if (!is_dir($this->distPath)) {
            mkdir($this->distPath, 0o777, true);
        }

        // Guarantee the "assets installed" sentinel used by FrontendAssets::exists().
        $this->writeFixture('index.html', '<!doctype html>');
        $this->writeFixture('bundle.js', 'console.log("panel");');
        $this->writeFixture('bundle.css', 'body{}');

        if (!is_dir($this->distPath . '/toolbar')) {
            mkdir($this->distPath . '/toolbar', 0o777, true);
        }
        $this->writeFixture('toolbar/bundle.js', 'console.log("toolbar");');
    }

    protected function tearDown(): void
    {
        foreach ($this->fixtureFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        // Remove only directories we may have created; leave anything else alone.
        @rmdir($this->distPath . '/toolbar');
        @rmdir($this->distPath);
    }

    public function testServesPanelBundle(): void
    {
        $controller = new FrontendAssetsController();

        $response = $controller('bundle.js');

        self::assertInstanceOf(BinaryFileResponse::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('application/javascript; charset=utf-8', $response->headers->get('Content-Type'));
        self::assertStringContainsString('immutable', (string) $response->headers->get('Cache-Control'));
    }

    public function testServesToolbarBundle(): void
    {
        $controller = new FrontendAssetsController();

        $response = $controller('toolbar/bundle.js');

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('application/javascript; charset=utf-8', $response->headers->get('Content-Type'));
    }

    public function testReturnsNotFoundForMissingFile(): void
    {
        $controller = new FrontendAssetsController();

        $response = $controller('does-not-exist.js');

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testRejectsPathTraversal(): void
    {
        $controller = new FrontendAssetsController();

        $response = $controller('../../../../etc/passwd');

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testRejectsDirectoryPath(): void
    {
        $controller = new FrontendAssetsController();

        $response = $controller('toolbar');

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testContentTypeMapping(): void
    {
        $this->writeFixture('bundle.css', 'body{}');
        $this->writeFixture('manifest.json', '{}');
        $this->writeFixture('favicon.ico', 'icon');

        $controller = new FrontendAssetsController();

        self::assertSame('text/css; charset=utf-8', $controller('bundle.css')->headers->get('Content-Type'));
        self::assertSame('application/json; charset=utf-8', $controller('manifest.json')->headers->get('Content-Type'));
        self::assertSame('image/x-icon', $controller('favicon.ico')->headers->get('Content-Type'));
    }

    private function writeFixture(string $relativePath, string $contents): void
    {
        $absolute = $this->distPath . '/' . $relativePath;

        // If the file pre-exists (already-built dist), don't touch it — treat it as
        // the real fixture. We only track and clean up files we actually wrote.
        if (is_file($absolute)) {
            return;
        }

        $dir = \dirname($absolute);
        if (!is_dir($dir)) {
            mkdir($dir, 0o777, true);
        }

        file_put_contents($absolute, $contents);
        $this->fixtureFiles[] = $absolute;
    }
}
