<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Api;

use AppDevPanel\Api\Toolbar\ToolbarInjector;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 middleware that injects the ADP debug toolbar into HTML responses.
 *
 * Should be placed early in the middleware stack (after DebugHeaders, before ErrorCatcher)
 * so that it can modify the response body before it is emitted.
 */
final class ToolbarMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ToolbarInjector $toolbarInjector,
        private readonly DebuggerIdGenerator $idGenerator,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!$this->toolbarInjector->isEnabled()) {
            return $response;
        }

        $contentType = '';
        foreach ($response->getHeader('Content-Type') as $value) {
            $contentType .= $value;
        }

        if (!$this->toolbarInjector->isHtmlResponse($contentType)) {
            return $response;
        }

        $body = (string) $response->getBody();
        if ($body === '') {
            return $response;
        }

        $uri = $request->getUri();
        $backendUrl = sprintf('%s://%s', $uri->getScheme(), $uri->getAuthority());

        $injected = $this->toolbarInjector->inject($body, $backendUrl, $this->idGenerator->getId());

        return $response->withBody($this->streamFactory->createStream($injected));
    }
}
