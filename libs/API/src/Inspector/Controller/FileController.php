<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\PathResolverInterface;
use FilesystemIterator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RecursiveDirectoryIterator;
use ReflectionClass;
use SplFileInfo;

final class FileController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly PathResolverInterface $pathResolver,
    ) {}

    public function files(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $class = $queryParams['class'] ?? '';
        $method = $queryParams['method'] ?? '';

        if (!empty($class) && class_exists($class)) {
            $reflection = new ReflectionClass($class);
            $destination = $reflection->getFileName();
            if ($method !== '' && $reflection->hasMethod($method)) {
                $reflectionMethod = $reflection->getMethod($method);
                $startLine = $reflectionMethod->getStartLine();
                $endLine = $reflectionMethod->getEndLine();
            }
            if ($destination === false) {
                return $this->responseFactory->createJsonResponse([
                    'message' => sprintf('Cannot find source of class "%s".', $class),
                ], 404);
            }
            return $this->readFile($destination, [
                'startLine' => $startLine ?? null,
                'endLine' => $endLine ?? null,
            ]);
        }

        $path = $queryParams['path'] ?? '';

        $rootPath = realpath($this->pathResolver->getRootPath());

        $destination = $this->removeBasePath($rootPath, $path);

        if (!str_starts_with($destination, '/')) {
            $destination = '/' . $destination;
        }

        $destination = realpath($rootPath . $destination);

        if ($destination === false) {
            return $this->responseFactory->createJsonResponse([
                'message' => sprintf('Destination "%s" does not exist', $path),
            ], 404);
        }

        if (!str_starts_with($destination, $rootPath)) {
            return $this->responseFactory->createJsonResponse([
                'message' => 'Access denied: path is outside the project root.',
            ], 403);
        }

        if (!is_dir($destination)) {
            return $this->readFile($destination);
        }

        $directoryIterator = new RecursiveDirectoryIterator(
            $destination,
            FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO,
        );

        $files = [];
        foreach ($directoryIterator as $file) {
            if ($file->getBasename() === '.') {
                continue;
            }

            $path = $file->getPathName();
            if ($file->isDir()) {
                if ($file->getBasename() === '..') {
                    $path = realpath($path);
                }
                $path .= '/';
            }
            if (!str_starts_with($path, $rootPath)) {
                continue;
            }
            $path = $this->removeBasePath($rootPath, $path);
            $files[] = array_merge([
                'path' => $path,
            ], $this->serializeFileInfo($file));
        }

        return $this->responseFactory->createJsonResponse($files);
    }

    private function removeBasePath(string $rootPath, string $path): string|array|null
    {
        return preg_replace('/^' . preg_quote($rootPath, '/') . '/', '', $path, 1);
    }

    private function getUserOwner(int $uid): array
    {
        if ($uid === 0 || !function_exists('posix_getpwuid') || false === ($info = posix_getpwuid($uid))) {
            return [
                'id' => $uid,
            ];
        }
        return [
            'uid' => $info['uid'],
            'gid' => $info['gid'],
            'name' => $info['name'],
        ];
    }

    private function getGroupOwner(int $gid): array
    {
        if ($gid === 0 || !function_exists('posix_getgrgid') || false === ($info = posix_getgrgid($gid))) {
            return [
                'id' => $gid,
            ];
        }
        return [
            'gid' => $info['gid'],
            'name' => $info['name'],
        ];
    }

    private function serializeFileInfo(SplFileInfo $file): array
    {
        return [
            'baseName' => $file->getBasename(),
            'extension' => $file->getExtension(),
            'user' => $this->getUserOwner((int) $file->getOwner()),
            'group' => $this->getGroupOwner((int) $file->getGroup()),
            'size' => $file->getSize(),
            'type' => $file->getType(),
            'permissions' => substr(sprintf('%o', $file->getPerms()), -4),
            'mtime' => $file->getMTime(),
        ];
    }

    private function readFile(string $destination, array $extra = []): ResponseInterface
    {
        $rootPath = $this->pathResolver->getRootPath();
        $file = new SplFileInfo($destination);
        return $this->responseFactory->createJsonResponse(array_merge(
            $extra,
            [
                'directory' => $this->removeBasePath($rootPath, dirname($destination)),
                'content' => file_get_contents($destination),
                'path' => $this->removeBasePath($rootPath, $destination),
                'absolutePath' => $destination,
            ],
            $this->serializeFileInfo($file),
        ));
    }
}
