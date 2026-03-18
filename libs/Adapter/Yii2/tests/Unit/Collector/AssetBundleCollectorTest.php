<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Collector;

use AppDevPanel\Adapter\Yii2\Collector\AssetBundleCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;
use yii\web\AssetBundle;

final class AssetBundleCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new AssetBundleCollector(new TimelineCollector());
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        /** @var AssetBundleCollector $collector */
        $bundle = new AssetBundle();
        $bundle->sourcePath = '@app/assets';
        $bundle->basePath = '/var/www/assets';
        $bundle->baseUrl = '/assets';
        $bundle->css = ['main.css'];
        $bundle->js = ['app.js'];
        $bundle->depends = ['yii\web\JqueryAsset'];
        $bundle->cssOptions = [];
        $bundle->jsOptions = [];
        $bundle->publishOptions = [];

        $collector->collectBundles(['app' => $bundle]);
    }

    protected function checkCollectedData(array $data): void
    {
        $this->assertArrayHasKey('bundles', $data);
        $this->assertArrayHasKey('bundleCount', $data);
        $this->assertSame(1, $data['bundleCount']);
        $this->assertArrayHasKey('app', $data['bundles']);

        $bundle = $data['bundles']['app'];
        $this->assertSame(AssetBundle::class, $bundle['class']);
        $this->assertSame('@app/assets', $bundle['sourcePath']);
        $this->assertSame(['main.css'], $bundle['css']);
        $this->assertSame(['app.js'], $bundle['js']);
        $this->assertSame(['yii\web\JqueryAsset'], $bundle['depends']);
    }

    protected function checkSummaryData(array $data): void
    {
        $this->assertArrayHasKey('assets', $data);
        $this->assertSame(1, $data['assets']['bundleCount']);
    }

    public function testCollectBundlesIgnoredWhenInactive(): void
    {
        $collector = new AssetBundleCollector(new TimelineCollector());

        $bundle = new AssetBundle();
        $collector->collectBundles(['test' => $bundle]);

        $this->assertSame([], $collector->getCollected());
    }

    public function testResetClearsData(): void
    {
        $collector = new AssetBundleCollector(new TimelineCollector());
        $collector->startup();

        $bundle = new AssetBundle();
        $collector->collectBundles(['test' => $bundle]);
        $this->assertSame(1, $collector->getCollected()['bundleCount']);

        $collector->shutdown();
        $collector->startup();
        $this->assertSame(0, $collector->getCollected()['bundleCount']);
    }

    public function testMultipleBundles(): void
    {
        $collector = new AssetBundleCollector(new TimelineCollector());
        $collector->startup();

        $bundle1 = new AssetBundle();
        $bundle1->css = ['a.css'];

        $bundle2 = new AssetBundle();
        $bundle2->js = ['b.js'];

        $collector->collectBundles(['first' => $bundle1, 'second' => $bundle2]);

        $data = $collector->getCollected();
        $this->assertSame(2, $data['bundleCount']);
        $this->assertArrayHasKey('first', $data['bundles']);
        $this->assertArrayHasKey('second', $data['bundles']);
    }
}
