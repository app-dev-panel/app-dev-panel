<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Debug\Middleware;

use AppDevPanel\Kernel\DebuggerIdGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds debug headers to response. Information from these headers may be used to request information about
 * the current request as it is done in the debug toolbar.
 */
final class DebugHeaders implements MiddlewareInterface
{
    public function __construct(
        private readonly DebuggerIdGenerator $idGenerator,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $link = '/debug/api/view/' . $this->idGenerator->getId();

        return $response->withHeader('X-Debug-Id', $this->idGenerator->getId())->withHeader('X-Debug-Link', $link);
    }
}
