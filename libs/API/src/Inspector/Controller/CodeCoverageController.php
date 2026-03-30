<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\PathResolverInterface;
use AppDevPanel\Kernel\Collector\CodeCoverageHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CodeCoverageController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly PathResolverInterface $pathResolver,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $driver = CodeCoverageHelper::detectDriver();

        if ($driver === null) {
            return $this->responseFactory->createJsonResponse([
                'driver' => null,
                'error' => 'No code coverage driver available (install pcov or xdebug)',
                'files' => [],
                'summary' => CodeCoverageHelper::buildSummary([], 0, 0),
            ]);
        }

        $rawCoverage = $this->collectRawCoverage($driver);
        $result = CodeCoverageHelper::processCoverage($rawCoverage);

        return $this->responseFactory->createJsonResponse([
            'driver' => $driver,
            'files' => $result['files'],
            'summary' => CodeCoverageHelper::buildSummary(
                $result['files'],
                $result['coveredLines'],
                $result['executableLines'],
            ),
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

        $rootPath = realpath($this->pathResolver->getRootPath());
        $realPath = realpath($path);

        if ($realPath === false || !is_file($realPath)) {
            return $this->responseFactory->createJsonResponse([
                'message' => sprintf('File "%s" does not exist', $path),
            ], 404);
        }

        if (!str_starts_with($realPath, $rootPath)) {
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

    /**
     * @return array<string, array<int, int>>
     */
    private function collectRawCoverage(string $driver): array
    {
        match ($driver) {
            'pcov' => \pcov\start(),
            'xdebug' => \xdebug_start_code_coverage(\XDEBUG_CC_UNUSED | \XDEBUG_CC_DEAD_CODE),
            default => throw new \LogicException(sprintf('Unsupported coverage driver: %s', $driver)),
        };

        /** @var array<string, array<int, int>> $rawCoverage */
        $rawCoverage = match ($driver) {
            'pcov' => $this->stopPcov(),
            'xdebug' => $this->stopXdebug(),
        };

        return $rawCoverage;
    }

    /**
     * @return array<string, array<int, int>>
     */
    private function stopPcov(): array
    {
        \pcov\stop();
        /** @var array<string, array<int, int>> */
        return \pcov\collect(\pcov\inclusive, '.');
    }

    /**
     * @return array<string, array<int, int>>
     */
    private function stopXdebug(): array
    {
        /** @var array<string, array<int, int>> $coverage */
        $coverage = \xdebug_get_code_coverage();
        \xdebug_stop_code_coverage();

        return $coverage;
    }
}
