<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Inspector;

use Psr\Http\Message\ServerRequestInterface;
use yii\web\UrlManager;

/**
 * Adapts Yii 2's UrlManager::parseRequest() to the URL matcher interface
 * expected by {@see \AppDevPanel\Api\Inspector\Controller\RoutingController::checkRoute()}.
 */
final class Yii2UrlMatcherAdapter
{
    public function __construct(
        private readonly UrlManager $urlManager,
    ) {}

    public function match(ServerRequestInterface $request): Yii2MatchResult
    {
        $pathInfo = ltrim($request->getUri()->getPath(), '/');
        $method = $request->getMethod();

        $yiiRequest = new \yii\web\Request();

        // Set pathInfo directly via reflection to bypass URL resolution complexity
        $pathInfoProperty = new \ReflectionProperty(\yii\web\Request::class, '_pathInfo');
        $pathInfoProperty->setValue($yiiRequest, $pathInfo);

        // Yii 2 UrlRule reads HTTP method from the request object, which reads $_SERVER
        $_serverBackup = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = $method;

        try {
            $result = $this->urlManager->parseRequest($yiiRequest);
        } finally {
            $_SERVER = $_serverBackup;
        }

        if ($result === false) {
            return new Yii2MatchResult(false);
        }

        [$route] = $result;

        return new Yii2MatchResult(true, $route);
    }
}
