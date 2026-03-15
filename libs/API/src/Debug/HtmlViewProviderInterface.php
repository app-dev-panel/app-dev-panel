<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Debug;

use AppDevPanel\Kernel\Collector\CollectorInterface;

interface HtmlViewProviderInterface extends CollectorInterface
{
    /**
     * Returns file path to the view file will be rendered when collector data is requested.
     * Example:
     * ```php
     * public static function getViewPath(): string
     * {
     *     return '@views/debug/index';
     * }
     * ```
     */
    public static function getViewPath(): string;
}
