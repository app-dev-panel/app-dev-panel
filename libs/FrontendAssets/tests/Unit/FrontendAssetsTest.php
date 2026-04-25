<?php

declare(strict_types=1);

namespace AppDevPanel\FrontendAssets\Tests\Unit;

use AppDevPanel\FrontendAssets\FrontendAssets;
use PHPUnit\Framework\TestCase;

final class FrontendAssetsTest extends TestCase
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
        $this->backupFile = sys_get_temp_dir() . '/adp-fa-test-' . uniqid() . '.bak';
        if (is_file($indexPath)) {
            copy($indexPath, $this->backupFile);
        } else {
            file_put_contents($indexPath, '<!doctype html><title>t</title>');
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
        foreach (['bundle.js', 'bundle.css', 'assets/x-aBc12345.js'] as $rel) {
            $p = $distDir . '/' . $rel;
            if (is_file($p) && str_contains((string) file_get_contents($p), 'fa-test-fixture')) {
                unlink($p);
                $dir = dirname($p);
                if ($dir !== $distDir && is_dir($dir) && count((array) @scandir($dir)) <= 2) {
                    @rmdir($dir);
                }
            }
        }
    }

    public function testUrlPrefixConstant(): void
    {
        $this->assertSame('/debug-assets', FrontendAssets::URL_PREFIX);
    }

    public function testIsAvailableMatchesExists(): void
    {
        $this->assertSame(FrontendAssets::exists(), FrontendAssets::isAvailable());
    }

    public function testResolveValidFile(): void
    {
        $distDir = FrontendAssets::path();
        file_put_contents($distDir . '/bundle.js', '// fa-test-fixture');

        $result = FrontendAssets::resolve('bundle.js');
        $this->assertNotNull($result);
        $this->assertSame(realpath($distDir . '/bundle.js'), $result);
    }

    public function testResolveRejectsTraversal(): void
    {
        $this->assertNull(FrontendAssets::resolve('../secret.txt'));
        $this->assertNull(FrontendAssets::resolve('/etc/passwd'));
    }

    public function testResolveRejectsMissingFile(): void
    {
        $this->assertNull(FrontendAssets::resolve('does-not-exist.js'));
    }

    public function testResolveRejectsDistDirItself(): void
    {
        // Resolving '' or '.' must not return the dist directory as a file.
        $this->assertNull(FrontendAssets::resolve(''));
        $this->assertNull(FrontendAssets::resolve('.'));
    }

    public function testMimeForKnownExtensions(): void
    {
        $this->assertSame('application/javascript; charset=utf-8', FrontendAssets::mimeFor('bundle.js'));
        $this->assertSame('text/css; charset=utf-8', FrontendAssets::mimeFor('bundle.css'));
        $this->assertSame('image/svg+xml', FrontendAssets::mimeFor('icon.svg'));
        $this->assertSame('font/woff2', FrontendAssets::mimeFor('inter.woff2'));
    }

    public function testMimeForUnknownExtension(): void
    {
        $this->assertSame('application/octet-stream', FrontendAssets::mimeFor('unknown.xyz'));
    }

    public function testCacheControlForHashedAsset(): void
    {
        $this->assertSame(
            'public, max-age=31536000, immutable',
            FrontendAssets::cacheControlFor('/dist/assets/preload-helper-XGcBzeW.js'),
        );
        $this->assertSame(
            'public, max-age=31536000, immutable',
            FrontendAssets::cacheControlFor('/dist/assets/Inter-aBc123XYZ.woff2'),
        );
    }

    public function testCacheControlForEntryPoint(): void
    {
        $this->assertSame('public, max-age=300, must-revalidate', FrontendAssets::cacheControlFor('/dist/bundle.js'));
        $this->assertSame('public, max-age=300, must-revalidate', FrontendAssets::cacheControlFor('/dist/index.html'));
    }
}
