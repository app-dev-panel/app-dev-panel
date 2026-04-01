<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Inspector;

use yii\web\UrlRule;

/**
 * Wraps a single Yii 2 UrlRule to expose __debugInfo()
 * in the format expected by RoutingController.
 */
final class Yii2RouteAdapter
{
    /**
     * @param array{string, string}|null $resolvedAction [className, methodName] if resolved
     */
    public function __construct(
        private readonly ?UrlRule $rule = null,
        private readonly string $fallbackClass = '',
        private readonly ?array $resolvedAction = null,
    ) {}

    public function __debugInfo(): array
    {
        if ($this->rule === null) {
            return [
                'name' => $this->fallbackClass,
                'hosts' => [],
                'pattern' => $this->fallbackClass,
                'methods' => [],
                'defaults' => [],
                'override' => 0,
                'middlewares' => [],
            ];
        }

        $middlewares = $this->resolvedAction !== null ? [$this->resolvedAction] : [$this->rule->route];

        return [
            'name' => $this->rule->name,
            'hosts' => $this->rule->host ? [$this->rule->host] : [],
            'pattern' => $this->rule->name,
            'methods' => $this->rule->verb ?? [],
            'defaults' => $this->rule->defaults,
            'override' => 0,
            'middlewares' => $middlewares,
        ];
    }
}
