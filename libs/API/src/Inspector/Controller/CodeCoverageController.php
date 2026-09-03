<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Inspector\Coverage\StoredCoverageReader;
use AppDevPanel\Api\PathResolver;
use AppDevPanel\Api\PathResolverInterface;
use AppDevPanel\Kernel\Collector\CodeCoverageHelper;
use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Serves code coverage recorded by `CodeCoverageCollector` for a stored debug entry.
 *
 * Starting/stopping the driver inside the inspector request would only ever cover
 * this controller (the old, misleading "0%" behaviour), so `index` reads the
 * collector payload of a debug entry instead: `?debugEntryId=<id>` or, when
 * omitted, the newest entry whose summary carries a `codeCoverage` block.
 * Without a {@see CollectorRepositoryInterface} the endpoint answers 501.
 */
final class CodeCoverageController
{
    private readonly ?StoredCoverageReader $reader;

    /** @var Closure(): ?string */
    private readonly Closure $driverDetector;

    /**
     * @param null|callable(): ?string $driverDetector Overrides `CodeCoverageHelper::detectDriver()` (tests).
     */
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly PathResolverInterface $pathResolver,
        ?CollectorRepositoryInterface $collectorRepository = null,
        ?callable $driverDetector = null,
    ) {
        $this->reader = $collectorRepository === null ? null : new StoredCoverageReader($collectorRepository);
        $this->driverDetector = $driverDetector === null
            ? CodeCoverageHelper::detectDriver(...)
            : Closure::fromCallable($driverDetector);
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $driver = ($this->driverDetector)();

        if ($driver === null) {
            return $this->errorResponse(null, 'No code coverage driver available (install pcov or xdebug)', 200);
        }

        if ($this->reader === null) {
            return $this->errorResponse(
                $driver,
                'Code coverage is recorded per request by CodeCoverageCollector; this endpoint needs a '
                . 'CollectorRepositoryInterface to read it. Wire one in the adapter.',
                501,
            );
        }

        $entryId = $this->reader->resolveEntryId($request->getQueryParams()['debugEntryId'] ?? null);
        if ($entryId === null) {
            return $this->errorResponse(
                $driver,
                'No debug entry with code coverage data found. Enable CodeCoverageCollector and make a request.',
                404,
            );
        }

        $collected = $this->reader->read($entryId);
        if ($collected === null) {
            return $this->errorResponse(
                $driver,
                sprintf('Debug entry "%s" has no CodeCoverageCollector data.', $entryId),
                404,
            );
        }

        return $this->responseFactory->createJsonResponse([
            'driver' => $collected['driver'] ?? $driver,
            'debugEntryId' => $entryId,
            'files' => $collected['files'],
            'summary' => $collected['summary'],
        ]);
    }

    public function file(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $path = $params['path'] ?? '';

        if ($path === '') {
            return $this->responseFactory->createJsonResponse([
                'message' => 'Missing required parameter: path',
            ], 400);
        }

        $realPath = realpath($path);

        if ($realPath === false || !is_file($realPath)) {
            return $this->responseFactory->createJsonResponse([
                'message' => sprintf('File "%s" does not exist', $path),
            ], 404);
        }

        if (!PathResolver::isInside($this->pathResolver->getRootPath(), $realPath)) {
            return $this->responseFactory->createJsonResponse([
                'message' => 'Access denied: path is outside the project root.',
            ], 403);
        }

        $content = file_get_contents($realPath);

        return $this->responseFactory->createJsonResponse([
            'path' => $realPath,
            'content' => $content,
            'lines' => $content !== false ? substr_count($content, "\n") + 1 : 0,
        ]);
    }

    private function errorResponse(?string $driver, string $error, int $status): ResponseInterface
    {
        return $this->responseFactory->createJsonResponse([
            'driver' => $driver,
            'error' => $error,
            'files' => [],
            'summary' => CodeCoverageHelper::buildSummary([], 0, 0),
        ], $status);
    }
}
