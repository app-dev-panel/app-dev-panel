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

        $jsAsset = $this->createRealAsset('app.js', '/src/app.js', '/assets/app.123.js');
        $cssAsset = $this->createRealAsset('styles.css', '/src/styles.css', '/assets/styles.456.css');

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

        $depAsset = $this->createRealAsset('vendor.js', '/src/vendor.js', '/assets/vendor.js');

        $mainAsset = new \Symfony\Component\AssetMapper\MappedAsset(
            logicalPath: 'app.js',
            sourcePath: '/src/app.js',
            publicPathWithoutDigest: '/assets/app.js',
            publicPath: '/assets/app.js',
        );
        $mainAsset->addDependency($depAsset);

        $assetMapper = $this->createMock(\Symfony\Component\AssetMapper\AssetMapperInterface::class);
        $assetMapper->method('allAssets')->willReturn(new \ArrayIterator([$mainAsset]));

        $subscriber = new AssetMapperSubscriber($collector, $assetMapper);
        $subscriber->onKernelResponse();

        $collected = $collector->getCollected();
        $this->assertSame(['vendor.js'], $collected['bundles']['app.js']['depends']);
    }

    private function createRealAsset(
        string $logicalPath,
        string $sourcePath,
        string $publicPath,
    ): \Symfony\Component\AssetMapper\MappedAsset {
        return new \Symfony\Component\AssetMapper\MappedAsset(
            logicalPath: $logicalPath,
            sourcePath: $sourcePath,
            publicPathWithoutDigest: $publicPath,
            publicPath: $publicPath,
        );
    }
}
