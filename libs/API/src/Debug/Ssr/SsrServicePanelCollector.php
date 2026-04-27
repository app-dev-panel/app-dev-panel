<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Debug\Ssr;

use AppDevPanel\Api\Debug\HtmlViewProviderInterface;
use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\ServiceCollector;

/**
 * Server-rendered view of the {@see ServiceCollector}'s tracked method calls.
 *
 * Mirrors the wrapped collector at flush time. The view template renders the
 * service id and class through `<ClassName>` slots, the method as monospaced
 * text, and arguments/result through `<JsonRenderer>` slots — everything
 * hydrated client-side via `SsrPanel`'s portal mechanism.
 */
final class SsrServicePanelCollector implements HtmlViewProviderInterface
{
    use CollectorTrait;

    public function __construct(
        private readonly ServiceCollector $serviceCollector,
    ) {}

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return $this->serviceCollector->getCollected();
    }

    public static function getViewPath(): string
    {
        return __DIR__ . '/service-panel.php';
    }
}
