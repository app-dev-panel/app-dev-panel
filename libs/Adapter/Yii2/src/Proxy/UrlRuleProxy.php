<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Proxy;

use yii\web\UrlRuleInterface;

/**
 * Wraps a Yii 2 URL rule to intercept successful route matching.
 *
 * When parseRequest() returns a match, the matched rule and result are
 * recorded in RouterMatchRecorder for later extraction by WebListener.
 */
final class UrlRuleProxy implements UrlRuleInterface
{
    public function __construct(
        private readonly UrlRuleInterface $inner,
        private readonly RouterMatchRecorder $recorder,
    ) {}

    public function parseRequest($manager, $request): array|false
    {
        $this->recorder->markStartIfNeeded();
        $result = $this->inner->parseRequest($manager, $request);
        if ($result !== false) {
            $this->recorder->recordMatch($this->inner, $result);
        }
        return $result;
    }

    public function createUrl($manager, $route, $params): string|false
    {
        return $this->inner->createUrl($manager, $route, $params);
    }

    /**
     * Returns the original (unwrapped) URL rule.
     */
    public function getInnerRule(): UrlRuleInterface
    {
        return $this->inner;
    }
}
