<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Collector\Router;

use AppDevPanel\Kernel\Collector\RouterCollector;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\UrlMatcherInterface;

final class UrlMatcherInterfaceProxy implements UrlMatcherInterface
{
    public function __construct(
        private readonly UrlMatcherInterface $urlMatcher,
        private readonly RouterCollector $routerCollector,
    ) {}

    public function match(ServerRequestInterface $request): MatchingResult
    {
        $timeStart = microtime(true);
        $result = $this->urlMatcher->match($request);
        $this->routerCollector->collectMatchTime(microtime(true) - $timeStart);

        return $result;
    }
}
