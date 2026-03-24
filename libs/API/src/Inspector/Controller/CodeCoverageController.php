<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\PathResolverInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CodeCoverageController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly PathResolverInterface $pathResolver,
        private readonly array $includePaths = [],
        private readonly array $excludePaths = ['vendor'],
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $driver = $this->detectDriver();

        if ($driver === null) {
            return $this->responseFactory->createJsonResponse([
                'driver' => null,
                'error' => 'No code coverage driver available (install pcov or xdebug)',
                'files' => [],
                'summary' => [
                    'totalFiles' => 0,
                    'coveredLines' => 0,
                    'executableLines' => 0,
                    'percentage' => 0.0,
                ],
            ]);
        }

        $coverage = $this->collectCoverage($driver);

        return $this->responseFactory->createJsonResponse($coverage);
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

    private function detectDriver(): ?string
    {
        if (\extension_loaded('pcov') && \ini_get('pcov.enabled')) {
            return 'pcov';
        }

        if (\extension_loaded('xdebug') && \in_array('coverage', \xdebug_info('mode'), true)) {
            return 'xdebug';
        }

        return null;
    }

    private function collectCoverage(string $driver): array
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

        return $this->processCoverage($driver, $rawCoverage);
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

    /**
     * @param array<string, array<int, int>> $rawCoverage
     */
    private function processCoverage(string $driver, array $rawCoverage): array
    {
        $files = [];
        $totalCovered = 0;
        $totalExecutable = 0;

        foreach ($rawCoverage as $file => $lines) {
            if (!$this->shouldIncludeFile($file)) {
                continue;
            }

            $coveredLines = 0;
            $executableLines = 0;

            foreach ($lines as $status) {
                if ($status === 1) {
                    $coveredLines++;
                    $executableLines++;
                } elseif ($status === -1) {
                    $executableLines++;
                }
            }

            if ($executableLines === 0) {
                continue;
            }

            $files[$file] = [
                'coveredLines' => $coveredLines,
                'executableLines' => $executableLines,
                'percentage' => round(($coveredLines / $executableLines) * 100, 2),
            ];

            $totalCovered += $coveredLines;
            $totalExecutable += $executableLines;
        }

        return [
            'driver' => $driver,
            'files' => $files,
            'summary' => [
                'totalFiles' => count($files),
                'coveredLines' => $totalCovered,
                'executableLines' => $totalExecutable,
                'percentage' => $totalExecutable > 0 ? round(($totalCovered / $totalExecutable) * 100, 2) : 0.0,
            ],
        ];
    }

    private function shouldIncludeFile(string $file): bool
    {
        foreach ($this->excludePaths as $excludePath) {
            if (
                str_contains($file, DIRECTORY_SEPARATOR . $excludePath . DIRECTORY_SEPARATOR)
                || str_contains($file, '/' . $excludePath . '/')
            ) {
                return false;
            }
        }

        if ($this->includePaths === []) {
            return true;
        }

        foreach ($this->includePaths as $includePath) {
            if (str_contains($file, $includePath)) {
                return true;
            }
        }

        return false;
    }
}
