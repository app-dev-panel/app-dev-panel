<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\EventSubscriber;

use AppDevPanel\Adapter\Symfony\EventSubscriber\AssetMapperSubscriber;
use AppDevPanel\Kernel\Collector\AssetBundleCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelEvents;

final class AssetMapperSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        if (!interface_exists(\Symfony\Component\AssetMapper\AssetMapperInterface::class, true)) {
            $this->markTestSkipped('symfony/asset-mapper is not installed.');
        }
    }

    public function testSubscribesToKernelResponse(): void
    {
        $events = AssetMapperSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
        $this->assertSame(['onKernelResponse', -512], $events[KernelEvents::RESPONSE]);
    }

    public function testCollectsMappedAssets(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new AssetBundleCollector($timeline);
        $collector->startup();

        $jsAsset = $this->createMockAsset('app.js', '/src/app.js', '/assets/app.123.js', []);
        $cssAsset = $this->createMockAsset('styles.css', '/src/styles.css', '/assets/styles.456.css', []);

        $assetMapper = $this->createMock(\Symfony\Component\AssetMapper\AssetMapperInterface::class);
        $assetMapper->method('allAssets')->willReturn(new \ArrayIterator([$jsAsset, $cssAsset]));

        $subscriber = new AssetMapperSubscriber($collector, $assetMapper);
        $subscriber->onKernelResponse();

        $collected = $collector->getCollected();
        $this->assertSame(2, $collected['bundleCount']);
        $this->assertArrayHasKey('app.js', $collected['bundles']);
        $this->assertArrayHasKey('styles.css', $collected['bundles']);
        $this->assertSame(['app.js'], $collected['bundles']['app.js']['js']);
        $this->assertSame(['styles.css'], $collected['bundles']['styles.css']['css']);
    }

    public function testSkipsCollectionWhenNoAssets(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new AssetBundleCollector($timeline);
        $collector->startup();

        $assetMapper = $this->createMock(\Symfony\Component\AssetMapper\AssetMapperInterface::class);
        $assetMapper->method('allAssets')->willReturn(new \ArrayIterator([]));

        $subscriber = new AssetMapperSubscriber($collector, $assetMapper);
        $subscriber->onKernelResponse();

        $collected = $collector->getCollected();
        $this->assertSame(0, $collected['bundleCount']);
    }

    public function testCollectsAssetDependencies(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new AssetBundleCollector($timeline);
        $collector->startup();

        $depAsset = $this->createMockAsset('vendor.js', '/src/vendor.js', '/assets/vendor.js', []);

        $dep = $this->createMock(\Symfony\Component\AssetMapper\ImportMap\JavaScriptImport::class);
        $dep->asset = $depAsset;

        $mainAsset = $this->createMockAsset('app.js', '/src/app.js', '/assets/app.js', [$dep]);

        $assetMapper = $this->createMock(\Symfony\Component\AssetMapper\AssetMapperInterface::class);
        $assetMapper->method('allAssets')->willReturn(new \ArrayIterator([$mainAsset]));

        $subscriber = new AssetMapperSubscriber($collector, $assetMapper);
        $subscriber->onKernelResponse();

        $collected = $collector->getCollected();
        $this->assertSame(['vendor.js'], $collected['bundles']['app.js']['depends']);
    }

    private function createMockAsset(
        string $logicalPath,
        string $sourcePath,
        string $publicPath,
        array $dependencies,
    ): object {
        $asset = $this->createMock(\Symfony\Component\AssetMapper\MappedAsset::class);
        $asset->logicalPath = $logicalPath;
        $asset->sourcePath = $sourcePath;
        $asset->publicPath = $publicPath;
        $asset->method('getDependencies')->willReturn($dependencies);

        return $asset;
    }
}
