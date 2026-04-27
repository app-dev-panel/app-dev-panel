<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\AssetBundleCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class AssetBundleCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new AssetBundleCollector(new TimelineCollector());
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        /** @var AssetBundleCollector $collector */
        $collector->collectBundles([
            'app' => [
                'class' => 'App\\Assets\\AppAsset',
                'sourcePath' => '@app/assets',
                'basePath' => '/var/www/assets',
                'baseUrl' => '/assets',
                'css' => ['main.css'],
                'js' => ['app.js'],
                'depends' => ['yii\\web\\JqueryAsset'],
                'options' => [],
            ],
        ]);
    }

    protected function checkCollectedData(array $data): void
    {
        $this->assertArrayHasKey('bundles', $data);
        $this->assertArrayHasKey('bundleCount', $data);
        $this->assertSame(1, $data['bundleCount']);
        $this->assertArrayHasKey('app', $data['bundles']);

        $bundle = $data['bundles']['app'];
        $this->assertSame('App\\Assets\\AppAsset', $bundle['class']);
        $this->assertSame('@app/assets', $bundle['sourcePath']);
        $this->assertSame(['main.css'], $bundle['css']);
        $this->assertSame(['app.js'], $bundle['js']);
        $this->assertSame(['yii\\web\\JqueryAsset'], $bundle['depends']);
    }

    protected function checkSummaryData(array $data): void
    {
        $this->assertArrayHasKey('assets', $data);
        $this->assertSame(1, $data['assets']['bundleCount']);
    }

    public function testCollectBundlesIgnoredWhenInactive(): void
    {
        $collector = new AssetBundleCollector(new TimelineCollector());
        $baselineCollected = $collector->getCollected();
        $baselineSummary = method_exists($collector, 'getSummary') ? $collector->getSummary() : null;

        $collector->collectBundles([
            'test' => [
                'class' => 'Test\\Asset',
                'sourcePath' => null,
                'basePath' => null,
                'baseUrl' => null,
                'css' => [],
                'js' => [],
                'depends' => [],
                'options' => [],
            ],
        ]);

        $this->assertSame($baselineCollected, $collector->getCollected());
    }

    public function testCollectSingleBundle(): void
    {
        $collector = new AssetBundleCollector(new TimelineCollector());
        $collector->startup();

        $collector->collectBundle('jquery', [
            'class' => 'yii\\web\\JqueryAsset',
            'sourcePath' => '@bower/jquery/dist',
            'basePath' => '/var/www/assets',
            'baseUrl' => '/assets',
            'css' => [],
            'js' => ['jquery.js'],
            'depends' => [],
            'options' => [],
        ]);

        $data = $collector->getCollected();
        $this->assertSame(1, $data['bundleCount']);
        $this->assertArrayHasKey('jquery', $data['bundles']);
        $this->assertSame(['jquery.js'], $data['bundles']['jquery']['js']);
    }

    public function testResetClearsData(): void
    {
        $collector = new AssetBundleCollector(new TimelineCollector());
        $collector->startup();

        $collector->collectBundles([
            'test' => [
                'class' => 'Test\\Asset',
                'sourcePath' => null,
                'basePath' => null,
                'baseUrl' => null,
                'css' => [],
                'js' => [],
                'depends' => [],
                'options' => [],
            ],
        ]);
        $this->assertSame(1, $collector->getCollected()['bundleCount']);

        $collector->shutdown();
        $collector->startup();
        $this->assertSame(0, $collector->getCollected()['bundleCount']);
    }

    public function testCollectBundleIgnoredWhenInactive(): void
    {
        $collector = new AssetBundleCollector(new TimelineCollector());
        $baselineCollected = $collector->getCollected();
        $baselineSummary = method_exists($collector, 'getSummary') ? $collector->getSummary() : null;
        // Not started
        $collector->collectBundle('test', [
            'class' => 'Test\\Asset',
            'sourcePath' => null,
            'basePath' => null,
            'baseUrl' => null,
            'css' => [],
            'js' => [],
            'depends' => [],
            'options' => [],
        ]);

        $this->assertSame($baselineCollected, $collector->getCollected());
    }

    public function testMultipleBundles(): void
    {
        $collector = new AssetBundleCollector(new TimelineCollector());
        $collector->startup();

        $collector->collectBundles([
            'first' => [
                'class' => 'First\\Asset',
                'sourcePath' => null,
                'basePath' => null,
                'baseUrl' => null,
                'css' => ['a.css'],
                'js' => [],
                'depends' => [],
                'options' => [],
            ],
            'second' => [
                'class' => 'Second\\Asset',
                'sourcePath' => null,
                'basePath' => null,
                'baseUrl' => null,
                'css' => [],
                'js' => ['b.js'],
                'depends' => [],
                'options' => [],
            ],
        ]);

        $data = $collector->getCollected();
        $this->assertSame(2, $data['bundleCount']);
        $this->assertArrayHasKey('first', $data['bundles']);
        $this->assertArrayHasKey('second', $data['bundles']);
    }
}
