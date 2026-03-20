<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Captures registered asset bundles from the application.
 *
 * Framework adapters normalize their bundle data into arrays and call collectBundle() or collectBundles().
 * Each bundle is an associative array with keys: class, sourcePath, basePath, baseUrl, css, js, depends, options.
 */
final class AssetBundleCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<string, array{class: string, sourcePath: ?string, basePath: ?string, baseUrl: ?string, css: array, js: array, depends: array, options: array}> */
    private array $bundles = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    /**
     * Collect a single asset bundle.
     *
     * @param string $name Bundle identifier (typically FQCN or alias)
     * @param array{class: string, sourcePath: ?string, basePath: ?string, baseUrl: ?string, css: array, js: array, depends: array, options: array} $bundle Normalized bundle data
     */
    public function collectBundle(string $name, array $bundle): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->bundles[$name] = $bundle;
        $this->timelineCollector->collect($this, count($this->bundles));
    }

    /**
     * Collect multiple asset bundles at once.
     *
     * @param array<string, array{class: string, sourcePath: ?string, basePath: ?string, baseUrl: ?string, css: array, js: array, depends: array, options: array}> $bundles
     */
    public function collectBundles(array $bundles): void
    {
        if (!$this->isActive()) {
            return;
        }

        foreach ($bundles as $name => $bundle) {
            $this->bundles[$name] = $bundle;
        }

        $this->timelineCollector->collect($this, count($this->bundles));
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
