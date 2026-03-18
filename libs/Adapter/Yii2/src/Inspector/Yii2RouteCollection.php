<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Inspector;

use yii\web\UrlManager;
use yii\web\UrlRule;
use yii\web\UrlRuleInterface;

/**
 * Wraps Yii 2's UrlManager rules as an iterable route collection
 * compatible with RoutingController's expected interface.
 */
final class Yii2RouteCollection
{
    public function __construct(
        private readonly UrlManager $urlManager,
    ) {}

    /**
     * @return Yii2RouteAdapter[]
     */
    public function getRoutes(): array
    {
        $routes = [];
        foreach ($this->urlManager->rules as $rule) {
            if ($rule instanceof UrlRule) {
                $routes[] = new Yii2RouteAdapter($rule);
            } elseif ($rule instanceof UrlRuleInterface) {
                $routes[] = new Yii2RouteAdapter(null, $rule::class);
            }
        }
        return $routes;
    }
}
