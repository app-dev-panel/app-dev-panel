<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;

/**
 * Captures registered asset bundles from Yii 2's View component.
 *
 * Fed by View::EVENT_END_PAGE, registered in Module::registerAssetProfiling().
 * Reads View::$assetBundles to capture all registered bundles with their CSS/JS files.
 */
final class AssetBundleCollector implements CollectorInterface, SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<string, array{class: string, sourcePath: ?string, basePath: ?string, baseUrl: ?string, css: array, js: array, depends: array, options: array}> */
    private array $bundles = [];

    public function __construct(
        private readonly TimelineCollector $timeline,
    ) {}

    /**
     * @param \yii\web\AssetBundle[] $assetBundles Registered asset bundles from View::$assetBundles
     */
    public function collectBundles(array $assetBundles): void
    {
        if (!$this->isActive()) {
            return;
        }

        foreach ($assetBundles as $name => $bundle) {
            $this->bundles[$name] = [
                'class' => $bundle::class,
                'sourcePath' => $bundle->sourcePath,
                'basePath' => $bundle->basePath,
                'baseUrl' => $bundle->baseUrl,
                'css' => $bundle->css,
                'js' => $bundle->js,
                'depends' => $bundle->depends,
                'options' => array_filter([
                    'cssOptions' => $bundle->cssOptions,
                    'jsOptions' => $bundle->jsOptions,
                    'publishOptions' => $bundle->publishOptions,
                ]),
            ];
        }

        $this->timeline->collect($this, count($this->bundles));
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'bundles' => $this->bundles,
            'bundleCount' => count($this->bundles),
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'assets' => [
                'bundleCount' => count($this->bundles),
            ],
        ];
    }

    protected function reset(): void
    {
        $this->bundles = [];
    }
}
