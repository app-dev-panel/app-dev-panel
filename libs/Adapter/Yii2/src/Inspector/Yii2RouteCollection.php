<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Inspector;

use AppDevPanel\Adapter\Yii2\Proxy\UrlRuleProxy;
use yii\web\CompositeUrlRule;
use yii\web\UrlManager;
use yii\web\UrlRule;
use yii\web\UrlRuleInterface;

/**
 * Wraps Yii 2's UrlManager rules as an iterable route collection
 * compatible with RoutingController's expected interface.
 *
 * Unwraps UrlRuleProxy wrappers and extracts sub-rules from CompositeUrlRule (REST, Group).
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
            $innerRule = $rule instanceof UrlRuleProxy ? $rule->getInnerRule() : $rule;

            if ($innerRule instanceof UrlRule) {
                $resolved = $innerRule->route !== null ? Yii2ActionResolver::resolve($innerRule->route) : null;
                $routes[] = new Yii2RouteAdapter($innerRule, '', $resolved);
            } elseif ($innerRule instanceof CompositeUrlRule) {
                foreach ($this->extractCompositeSubRules($innerRule) as $adapter) {
                    $routes[] = $adapter;
                }
            } elseif ($innerRule instanceof UrlRuleInterface) {
                $routes[] = new Yii2RouteAdapter(null, $innerRule::class);
            }
        }
        return $routes;
    }

    /**
     * Extract sub-rules from CompositeUrlRule (REST, Group) via reflection.
     *
     * @return Yii2RouteAdapter[]
     */
    private function extractCompositeSubRules(CompositeUrlRule $compositeRule): array
    {
        try {
            $reflection = new \ReflectionProperty(CompositeUrlRule::class, 'rules');
            $subRules = $reflection->getValue($compositeRule);
        } catch (\ReflectionException) {
            return [new Yii2RouteAdapter(null, $compositeRule::class)];
        }

        // Sub-rules may be null (not yet initialized) — trigger via createRules()
        if ($subRules === null) {
            try {
                $createMethod = new \ReflectionMethod($compositeRule, 'createRules');
                $subRules = $createMethod->invoke($compositeRule);
            } catch (\ReflectionException) {
                return [new Yii2RouteAdapter(null, $compositeRule::class)];
            }
        }

        if (!is_array($subRules) || $subRules === []) {
            return [new Yii2RouteAdapter(null, $compositeRule::class)];
        }

        $adapters = [];
        foreach ($subRules as $subRule) {
            if ($subRule instanceof UrlRule) {
                $resolved = $subRule->route !== null ? Yii2ActionResolver::resolve($subRule->route) : null;
                $adapters[] = new Yii2RouteAdapter($subRule, '', $resolved);
            } elseif ($subRule instanceof UrlRuleInterface) {
                $adapters[] = new Yii2RouteAdapter(null, $subRule::class);
            }
        }

        return $adapters === [] ? [new Yii2RouteAdapter(null, $compositeRule::class)] : $adapters;
    }
}
