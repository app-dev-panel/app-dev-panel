<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Collector;

use AppDevPanel\Kernel\Collector\TemplateCollector;
use Illuminate\View\Engines\CompilerEngine;

/**
 * Wraps Laravel's CompilerEngine to capture Blade template rendering
 * into the framework-agnostic TemplateCollector.
 *
 * Measures render time and tracks template nesting depth.
 */
final class TemplateCollectorCompilerEngine extends CompilerEngine
{
    private ?TemplateCollector $collector = null;

    public function setCollector(TemplateCollector $collector): void
    {
        $this->collector = $collector;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function get($path, array $data = []): string
    {
        if ($this->collector === null) {
            return parent::get($path, $data);
        }

        $this->collector->beginRender($path);
        $startTime = microtime(true);

        try {
            $result = parent::get($path, $data);
        } catch (\Throwable $e) {
            $this->collector->endRender('', [], microtime(true) - $startTime);
            throw $e;
        }

        $this->collector->endRender($result, [], microtime(true) - $startTime);

        return $result;
    }
}
