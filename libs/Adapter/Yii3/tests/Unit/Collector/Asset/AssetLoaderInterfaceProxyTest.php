<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Collector\Asset;

use AppDevPanel\Adapter\Yii3\Collector\Asset\AssetLoaderInterfaceProxy;
use AppDevPanel\Kernel\Collector\AssetBundleCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;

final class AssetLoaderInterfaceProxyTest extends TestCase
{
    protected function setUp(): void
    {
        if (!interface_exists(\Yiisoft\Assets\AssetLoaderInterface::class, true)) {
            $this->markTestSkipped('yiisoft/assets is not installed.');
        }
    }

    public function testLoadBundleDelegatesToDecoratedAndCollectsBundle(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new AssetBundleCollector($timeline);
        $collector->startup();

        $bundle = $this->createMock(\Yiisoft\Assets\AssetBundle::class);
        $bundle->sourcePath = '/src/assets';
        $bundle->basePath = '/public/assets';
        $bundle->baseUrl = '/assets';
        $bundle->css = ['app.css'];
        $bundle->js = ['app.js'];
        $bundle->depends = [];
        $bundle->cssOptions = [];
        $bundle->jsOptions = [];
        $bundle->publishOptions = [];

        $decorated = $this->createMock(\Yiisoft\Assets\AssetLoaderInterface::class);
        $decorated->expects($this->once())
            ->method('loadBundle')
            ->with('App\\Assets\\MainAsset', [])
            ->willReturn($bundle);

        $proxy = new AssetLoaderInterfaceProxy($decorated, $collector);
        $result = $proxy->loadBundle('App\\Assets\\MainAsset');

        $this->assertSame($bundle, $result);

        $collected = $collector->getCollected();
        $this->assertSame(1, $collected['bundleCount']);
        $this->assertArrayHasKey('App\\Assets\\MainAsset', $collected['bundles']);
        $this->assertSame('/src/assets', $collected['bundles']['App\\Assets\\MainAsset']['sourcePath']);
        $this->assertSame(['app.css'], $collected['bundles']['App\\Assets\\MainAsset']['css']);
        $this->assertSame(['app.js'], $collected['bundles']['App\\Assets\\MainAsset']['js']);
    }

    public function testGetAssetUrlDelegatesToDecorated(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new AssetBundleCollector($timeline);
        $collector->startup();

        $bundle = $this->createMock(\Yiisoft\Assets\AssetBundle::class);

        $decorated = $this->createMock(\Yiisoft\Assets\AssetLoaderInterface::class);
        $decorated->expects($this->once())
            ->method('getAssetUrl')
            ->with($bundle, 'app.js')
            ->willReturn('/assets/app.js');

        $proxy = new AssetLoaderInterfaceProxy($decorated, $collector);
        $result = $proxy->getAssetUrl($bundle, 'app.js');

        $this->assertSame('/assets/app.js', $result);
    }

    public function testLoadMultipleBundles(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new AssetBundleCollector($timeline);
        $collector->startup();

        $bundle1 = $this->createMock(\Yiisoft\Assets\AssetBundle::class);
        $bundle1->sourcePath = null;
        $bundle1->basePath = null;
        $bundle1->baseUrl = null;
        $bundle1->css = ['style.css'];
        $bundle1->js = [];
        $bundle1->depends = [];
        $bundle1->cssOptions = [];
        $bundle1->jsOptions = [];
        $bundle1->publishOptions = [];

        $bundle2 = $this->createMock(\Yiisoft\Assets\AssetBundle::class);
        $bundle2->sourcePath = null;
        $bundle2->basePath = null;
        $bundle2->baseUrl = null;
        $bundle2->css = [];
        $bundle2->js = ['script.js'];
        $bundle2->depends = ['Bundle1'];
        $bundle2->cssOptions = [];
        $bundle2->jsOptions = [];
        $bundle2->publishOptions = [];

        $decorated = $this->createMock(\Yiisoft\Assets\AssetLoaderInterface::class);
        $decorated->method('loadBundle')
            ->willReturnOnConsecutiveCalls($bundle1, $bundle2);

        $proxy = new AssetLoaderInterfaceProxy($decorated, $collector);
        $proxy->loadBundle('Bundle1');
        $proxy->loadBundle('Bundle2');

        $collected = $collector->getCollected();
        $this->assertSame(2, $collected['bundleCount']);
    }

    public function testSummaryReflectsBundleCount(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new AssetBundleCollector($timeline);
        $collector->startup();

        $bundle = $this->createMock(\Yiisoft\Assets\AssetBundle::class);
        $bundle->sourcePath = null;
        $bundle->basePath = null;
        $bundle->baseUrl = null;
        $bundle->css = [];
        $bundle->js = [];
        $bundle->depends = [];
        $bundle->cssOptions = [];
        $bundle->jsOptions = [];
        $bundle->publishOptions = [];

        $decorated = $this->createMock(\Yiisoft\Assets\AssetLoaderInterface::class);
        $decorated->method('loadBundle')->willReturn($bundle);

        $proxy = new AssetLoaderInterfaceProxy($decorated, $collector);
        $proxy->loadBundle('TestBundle');

        $summary = $collector->getSummary();
        $this->assertSame(1, $summary['assets']['bundleCount']);
    }
}
