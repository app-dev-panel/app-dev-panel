<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\View;

use AppDevPanel\Kernel\Collector\TemplateCollector;
use Spiral\Views\ViewInterface;
use Spiral\Views\ViewsInterface;
use Throwable;

/**
 * Decorates `Spiral\Views\ViewsInterface` so every render is bracketed with
 * `TemplateCollector::beginRender()` / `endRender()` for nested-render hierarchy
 * tracking.
 *
 * Lives under the adapter namespace because it implements a Spiral-specific contract.
 * The collector handles inactive state internally — the proxy never short-circuits.
 */
final class TracingViews implements ViewsInterface
{
    public function __construct(
        private readonly ViewsInterface $inner,
        private readonly TemplateCollector $collector,
    ) {}

    public function render(string $path, array $data = []): string
    {
        $this->collector->beginRender($path);
        $start = microtime(true);

        try {
            $output = $this->inner->render($path, $data);
        } catch (Throwable $e) {
            $this->collector->endRender('', $data, microtime(true) - $start);
            throw $e;
        }

        $this->collector->endRender($output, $data, microtime(true) - $start);

        return $output;
    }

    public function get(string $path): ViewInterface
    {
        return $this->inner->get($path);
    }

    public function compile(string $path): void
    {
        $this->inner->compile($path);
    }

    public function reset(string $path): void
    {
        $this->inner->reset($path);
    }
}
