<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Debug\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\Debug\DebuggerIdGenerator;

/**
 * Adds debug headers to response. Information from these headers may be used to request information about
 * the current request as it is done in the debug toolbar.
 */
final class DebugHeaders implements MiddlewareInterface
{
    public function __construct(private DebuggerIdGenerator $idGenerator, private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $link = $this->urlGenerator->generate('debug/api/view', ['id' => $this->idGenerator->getId()]);

        return $response
            ->withHeader('X-Debug-Id', $this->idGenerator->getId())
            ->withHeader('X-Debug-Link', $link);
    }
}
