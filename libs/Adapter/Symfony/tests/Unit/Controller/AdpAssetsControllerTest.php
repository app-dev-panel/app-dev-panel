<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Controller;

use AppDevPanel\Adapter\Symfony\Controller\AdpAssetsController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Covers the static-asset streamer used by the Symfony adapter to serve panel +
 * toolbar bundles from `app-dev-panel/frontend-assets` without requiring the
 * user to run `bin/console assets:install`.
 */
final class AdpAssetsControllerTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . '/adp_assets_test_' . bin2hex(random_bytes(6));
        mkdir($this->baseDir . '/toolbar', 0o755, true);
        file_put_contents($this->baseDir . '/bundle.js', "console.log('panel');");
        file_put_contents($this->baseDir . '/bundle.css', 'body{}');
        file_put_contents($this->baseDir . '/toolbar/bundle.js', "console.log('toolbar');");
        file_put_contents($this->baseDir . '/index.html', '<!doctype html><title>adp</title>');
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->baseDir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->baseDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->baseDir);
    }

    public function testServesPanelBundle(): void
    {
        $controller = new AdpAssetsController($this->baseDir);

        $response = $controller('bundle.js');

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($this->baseDir . '/bundle.js', $response->getFile()->getPathname());
    }

    public function testServesToolbarBundleFromSubdirectory(): void
    {
        $controller = new AdpAssetsController($this->baseDir);

        $response = $controller('toolbar/bundle.js');

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame($this->baseDir . '/toolbar/bundle.js', $response->getFile()->getPathname());
    }

    public function testSetsEtagAndLastModified(): void
    {
        $controller = new AdpAssetsController($this->baseDir);

        $response = $controller('bundle.js');

        $this->assertNotSame('', (string) $response->getEtag());
        $this->assertNotNull($response->getLastModified());
    }

    public function testReturns404WhenFileDoesNotExist(): void
    {
        $controller = new AdpAssetsController($this->baseDir);

        $this->expectException(NotFoundHttpException::class);
        $controller('does-not-exist.js');
    }

    public function testReturns404ForPathTraversalAttempt(): void
    {
        $outside = sys_get_temp_dir() . '/adp_assets_outside_' . bin2hex(random_bytes(6));
        file_put_contents($outside, 'secret');

        try {
            $controller = new AdpAssetsController($this->baseDir);
            $relative = '../' . basename($outside);

            $caught = false;
            try {
                $controller($relative);
            } catch (NotFoundHttpException) {
                $caught = true;
            }

            $this->assertTrue($caught, 'Path traversal attempt must return 404 instead of streaming the target.');
        } finally {
            unlink($outside);
        }
    }

    public function testReturns404WhenBaseDirIsMissing(): void
    {
        $missing = sys_get_temp_dir() . '/adp_assets_missing_' . bin2hex(random_bytes(6));

        $controller = new AdpAssetsController($missing);

        $this->expectException(NotFoundHttpException::class);
        $controller('bundle.js');
    }

    public function testReturns404WhenTargetIsDirectory(): void
    {
        $controller = new AdpAssetsController($this->baseDir);

        $this->expectException(NotFoundHttpException::class);
        $controller('toolbar');
    }
}
