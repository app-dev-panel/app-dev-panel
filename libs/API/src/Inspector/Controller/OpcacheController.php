<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class OpcacheController
{
    /** @var Closure(): (array|false) */
    private readonly Closure $statusProvider;

    /** @var Closure(): array */
    private readonly Closure $configurationProvider;

    /**
     * @param (Closure(): (array|false))|null $statusProvider Returns `opcache_get_status(true)` or `false` when
     *        OPcache is unavailable. Defaults to the real extension call.
     * @param (Closure(): array)|null $configurationProvider Returns `opcache_get_configuration()`. Defaults to
     *        the real extension call.
     */
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        ?Closure $statusProvider = null,
        ?Closure $configurationProvider = null,
    ) {
        $this->statusProvider =
            $statusProvider
            ?? static fn(): array|false => \function_exists('opcache_get_status') ? \opcache_get_status(true) : false;
        $this->configurationProvider =
            $configurationProvider
            ?? static fn(): array => \function_exists('opcache_get_configuration') ? \opcache_get_configuration() : [];
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $status = ($this->statusProvider)();
        if ($status === false) {
            return $this->responseFactory->createJsonResponse([
                'message' => 'OPcache is not installed or configured',
            ], 422);
        }

        return $this->responseFactory->createJsonResponse([
            'status' => $status,
            'configuration' => ($this->configurationProvider)(),
        ]);
    }
}
