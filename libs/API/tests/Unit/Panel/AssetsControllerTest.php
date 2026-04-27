<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Panel;

use AppDevPanel\Api\Panel\AssetsController;
use GuzzleHttp\Psr7\HttpFactory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssetsControllerTest extends TestCase
{
    private string $dist;

    protected function setUp(): void
    {
        $this->dist = sys_get_temp_dir() . '/adp-assets-' . uniqid();
        mkdir($this->dist . '/toolbar/assets', 0o777, true);
        mkdir($this->dist . '/assets', 0o777, true);
        file_put_contents($this->dist . '/bundle.js', "console.log('panel');\n");
        file_put_contents($this->dist . '/bundle.css', "body{color:red}\n");
        file_put_contents($this->dist . '/favicon.ico', "\x00\x00\x01\x00");
        file_put_contents($this->dist . '/toolbar/bundle.js', "console.log('toolbar');\n");
        file_put_contents($this->dist . '/assets/Chunk-a1b2c3.js', "// hashed\n");
        file_put_contents($this->dist . '/toolbar/assets/Chunk-d4e5f6.js', "// hashed\n");
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->dist);
    }

    public function testServesBundleJsWithCorrectMimeAndShortRevalidatingCache(): void
    {
        $controller = $this->controller();
        $response = $controller->serve($this->request('bundle.js'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/javascript; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertSame('public, max-age=3600, must-revalidate', $response->getHeaderLine('Cache-Control'));
        $this->assertStringContainsString("console.log('panel');", (string) $response->getBody());
    }

    public function testServesHashedVitChunkWithImmutableCache(): void
    {
        $response = $this->controller()->serve($this->request('assets/Chunk-a1b2c3.js'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('public, max-age=31536000, immutable', $response->getHeaderLine('Cache-Control'));
    }

    public function testServesToolbarHashedChunkAsImmutable(): void
    {
        $response = $this->controller()->serve($this->request('toolbar/assets/Chunk-d4e5f6.js'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('public, max-age=31536000, immutable', $response->getHeaderLine('Cache-Control'));
    }

    public function testServesCssWithCorrectMime(): void
    {
        $response = $this->controller()->serve($this->request('bundle.css'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/css; charset=utf-8', $response->getHeaderLine('Content-Type'));
    }

    public function testServesBinaryFaviconWithIcoMime(): void
    {
        $response = $this->controller()->serve($this->request('favicon.ico'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains($response->getHeaderLine('Content-Type'), ['image/x-icon', 'image/vnd.microsoft.icon']);
    }

    public function testServesToolbarBundleFromNestedDirectory(): void
    {
        $response = $this->controller()->serve($this->request('toolbar/bundle.js'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString("console.log('toolbar');", (string) $response->getBody());
    }

    #[DataProvider('traversalAttempts')]
    public function testRejectsPathTraversalAttempts(string $path): void
    {
        $response = $this->controller()->serve($this->request($path));

        $this->assertSame(404, $response->getStatusCode());
    }

    public static function traversalAttempts(): iterable
    {
        yield 'parent dir' => ['../secrets.env'];
        yield 'double parent' => ['../../etc/passwd'];
        yield 'nested parent' => ['toolbar/../../etc/passwd'];
    }

    public function testReturns404ForMissingFile(): void
    {
        $response = $this->controller()->serve($this->request('nope.js'));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testReturns404ForEmptyPath(): void
    {
        $response = $this->controller()->serve($this->request(''));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testReturns404WhenAssetsRootDoesNotExist(): void
    {
        $controller = new AssetsController(new HttpFactory(), new HttpFactory(), '/tmp/no-such-adp-dir-' . uniqid());
        $response = $controller->serve($this->request('bundle.js'));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testStripsLeadingSlashFromPath(): void
    {
        $response = $this->controller()->serve($this->request('/bundle.js'));

        $this->assertSame(200, $response->getStatusCode());
    }

    private function controller(): AssetsController
    {
        $factory = new HttpFactory();

        return new AssetsController($factory, $factory, $this->dist);
    }

    private function request(string $path): ServerRequest
    {
        return new ServerRequest('GET', '/debug/static/' . $path)->withAttribute('path', $path);
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path . '/' . $item;
            is_dir($full) ? $this->removeTree($full) : @unlink($full);
        }
        @rmdir($path);
    }
}
