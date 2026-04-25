<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Controller;

use AppDevPanel\Adapter\Laravel\Controller\AdpAssetController;
use AppDevPanel\FrontendAssets\FrontendAssets;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AdpAssetControllerTest extends TestCase
{
    private string $backupFile;
    private bool $createdIndex = false;

    protected function setUp(): void
    {
        $distDir = FrontendAssets::path();
        if (!is_dir($distDir)) {
            mkdir($distDir, 0o777, true);
        }

        $indexPath = $distDir . '/index.html';
        $this->backupFile = sys_get_temp_dir() . '/adp-asset-test-laravel-' . uniqid() . '.bak';
        if (is_file($indexPath)) {
            copy($indexPath, $this->backupFile);
        } else {
            file_put_contents($indexPath, '<!doctype html><title>test</title>');
            $this->createdIndex = true;
        }
    }

    protected function tearDown(): void
    {
        $distDir = FrontendAssets::path();
        $indexPath = $distDir . '/index.html';

        if (is_file($this->backupFile)) {
            copy($this->backupFile, $indexPath);
            unlink($this->backupFile);
        } elseif ($this->createdIndex) {
            @unlink($indexPath);
        }

        foreach (['bundle.js', 'bundle.css', 'toolbar/bundle.js'] as $rel) {
            $path = $distDir . '/' . $rel;
            if (is_file($path) && str_contains(file_get_contents($path), 'test-fixture-payload')) {
                unlink($path);
                $dir = dirname($path);
                if ($dir !== $distDir && is_dir($dir) && count((array) @scandir($dir)) <= 2) {
                    @rmdir($dir);
                }
            }
        }
    }

    public function testServesPanelBundleJs(): void
    {
        $distDir = FrontendAssets::path();
        file_put_contents($distDir . '/bundle.js', "// test-fixture-payload\nconsole.log('adp');");

        $response = (new AdpAssetController())('bundle.js');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/javascript; charset=utf-8', $response->headers->get('Content-Type'));
    }

    public function testServesToolbarBundleFromNestedDirectory(): void
    {
        $distDir = FrontendAssets::path();
        if (!is_dir($distDir . '/toolbar')) {
            mkdir($distDir . '/toolbar', 0o777, true);
        }
        file_put_contents($distDir . '/toolbar/bundle.js', '// test-fixture-payload toolbar');

        $response = (new AdpAssetController())('toolbar/bundle.js');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/javascript; charset=utf-8', $response->headers->get('Content-Type'));
    }

    public function testRejectsDirectoryTraversal(): void
    {
        $this->expectException(NotFoundHttpException::class);

        (new AdpAssetController())('../secret.txt');
    }

    public function testReturns404ForMissingFile(): void
    {
        $this->expectException(NotFoundHttpException::class);

        (new AdpAssetController())('does-not-exist.js');
    }
}
