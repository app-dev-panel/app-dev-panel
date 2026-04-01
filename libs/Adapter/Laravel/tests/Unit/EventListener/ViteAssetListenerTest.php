<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\EventListener;

use AppDevPanel\Adapter\Laravel\EventListener\ViteAssetListener;
use AppDevPanel\Kernel\Collector\AssetBundleCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use Illuminate\Foundation\Vite;
use PHPUnit\Framework\TestCase;

final class ViteAssetListenerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(Vite::class, true)) {
            $this->markTestSkipped('illuminate/foundation is not installed.');
        }
    }

    public function testCollectsPreloadedViteAssets(): void
    {
        $collector = $this->createCollector();

        $vite = $this->createMock(Vite::class);
        $vite->method('preloadedAssets')->willReturn([
            '/build/app.js' => ['rel' => 'preload', 'as' => 'script'],
            '/build/app.css' => ['rel' => 'preload', 'as' => 'style'],
        ]);

        $listener = new ViteAssetListener(static fn() => $collector);
        $listener->collect($vite);

        $collected = $collector->getCollected();
        $this->assertSame(2, $collected['bundleCount']);
        $this->assertArrayHasKey('/build/app.js', $collected['bundles']);
        $this->assertArrayHasKey('/build/app.css', $collected['bundles']);
        $this->assertSame(['/build/app.js'], $collected['bundles']['/build/app.js']['js']);
        $this->assertSame(['/build/app.css'], $collected['bundles']['/build/app.css']['css']);
    }

    public function testSkipsWhenNoPreloadedAssets(): void
    {
        $collector = $this->createCollector();

        $vite = $this->createMock(Vite::class);
        $vite->method('preloadedAssets')->willReturn([]);

        $listener = new ViteAssetListener(static fn() => $collector);
        $listener->collect($vite);

        $collected = $collector->getCollected();
        $this->assertSame(0, $collected['bundleCount']);
    }

    public function testBundleMetadataIsCorrectlyNormalized(): void
    {
        $collector = $this->createCollector();

        $vite = $this->createMock(Vite::class);
        $vite->method('preloadedAssets')->willReturn([
            '/build/vendor.js' => ['rel' => 'preload', 'as' => 'script', 'crossorigin' => 'anonymous'],
        ]);

        $listener = new ViteAssetListener(static fn() => $collector);
        $listener->collect($vite);

        $collected = $collector->getCollected();
        $bundle = $collected['bundles']['/build/vendor.js'];
        $this->assertSame('Vite', $bundle['class']);
        $this->assertNull($bundle['sourcePath']);
        $this->assertNull($bundle['basePath']);
        $this->assertSame('/build/vendor.js', $bundle['baseUrl']);
        $this->assertSame([], $bundle['depends']);
    }

    private function createCollector(): AssetBundleCollector
    {
        $timeline = new TimelineCollector();
        $collector = new AssetBundleCollector($timeline);
        $timeline->startup();
        $collector->startup();
        return $collector;
    }
}
