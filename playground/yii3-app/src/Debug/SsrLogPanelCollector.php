<?php

declare(strict_types=1);

namespace App\Debug;

use AppDevPanel\Api\Debug\HtmlViewProviderInterface;
use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\LogCollector;

/**
 * SSR-rendered log panel collector for the Yii 3 playground.
 *
 * Demonstrates how a collector can ship its own server-rendered HTML view
 * by implementing {@see HtmlViewProviderInterface}. The collector itself owns
 * no proxy — it mirrors the live data of {@see LogCollector} at flush time, so
 * the view template receives the same log messages without duplicating capture.
 *
 * The panel UI sees this as just another collector entry; the API view endpoint
 * detects the interface, renders {@see self::getViewPath()} with `$data` exposed,
 * and ships the HTML to the panel as `{__html: "..."}`.
 */
final class SsrLogPanelCollector implements HtmlViewProviderInterface
{
    use CollectorTrait;

    public function __construct(
        private readonly LogCollector $logCollector,
    ) {}

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return $this->logCollector->getCollected();
    }

    public static function getViewPath(): string
    {
        return __DIR__ . '/template.php';
    }

    private function reset(): void {}
}
