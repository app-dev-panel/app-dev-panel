<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Marks a collector that ships its own server-rendered HTML view.
 *
 * When the panel requests collector data via `GET /debug/api/view/{id}?collector=Foo`
 * and `Foo` implements this interface, the API renders the view file returned by
 * {@see self::getViewPath()} and responds with `{"__html": "<...rendered HTML...>"}`
 * instead of the raw collector data. The frontend embeds the HTML as-is.
 *
 * Inside the view file, the collector's own data (whatever {@see CollectorInterface::getCollected()}
 * stored for the entry being viewed) is exposed as `$data`.
 *
 * The path must be an **absolute filesystem path** to a PHP template file —
 * framework aliases (`@views/...`) are not resolved. Typical usage:
 *
 * ```php
 * public static function getViewPath(): string
 * {
 *     return __DIR__ . '/template.php';
 * }
 * ```
 */
interface HtmlViewProviderInterface extends CollectorInterface
{
    /**
     * Absolute path to the PHP template file that renders this collector's data.
     */
    public static function getViewPath(): string;
}
