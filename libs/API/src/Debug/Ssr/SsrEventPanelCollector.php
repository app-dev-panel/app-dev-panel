<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Debug\Ssr;

use AppDevPanel\Api\Debug\HtmlViewProviderInterface;
use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\EventCollector;

/**
 * Server-rendered view of the {@see EventCollector}'s data.
 *
 * Mirrors the wrapped collector's items at flush time (no separate proxy/data
 * pipeline) and asks the API to render an HTML fragment via
 * {@see HtmlViewProviderInterface}. The frontend `SsrPanel` host hydrates the
 * embedded `Slot::*` markers (event class → `<ClassName>`, event payload →
 * `<JsonRenderer>`, caller location → `<FileLink>`), so the panel keeps theme,
 * Redux, and router context end-to-end.
 *
 * Lives alongside the regular `EventCollector` panel — both appear in the
 * sidebar; the SSR variant is opt-in (same as any other collector).
 */
final class SsrEventPanelCollector implements HtmlViewProviderInterface
{
    use CollectorTrait;

    public function __construct(
        private readonly EventCollector $eventCollector,
    ) {}

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return $this->eventCollector->getCollected();
    }

    public static function getViewPath(): string
    {
        return __DIR__ . '/event-panel.php';
    }
}
