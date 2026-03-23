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

        if ($class !== '' && class_exists($class)) {
            return $this->resolveClassFile($class, $method);
        }

        $path = $queryParams['path'] ?? '';

        return $this->resolvePathFile($path);
    }

    private function resolveClassFile(string $class, string $method): ResponseInterface
    {
        $reflection = new ReflectionClass($class);
        $destination = $reflection->getFileName();

        if ($destination === false) {
            return $this->responseFactory->createJsonResponse([
                'message' => sprintf('Cannot find source of class "%s".', $class),
            ], 404);
        }

        $extra = ['startLine' => null, 'endLine' => null];
        if ($method !== '' && $reflection->hasMethod($method)) {
            $reflectionMethod = $reflection->getMethod($method);
            $extra = [
                'startLine' => $reflectionMethod->getStartLine(),
                'endLine' => $reflectionMethod->getEndLine(),
            ];
        }

        return $this->readFile($destination, $extra);
    }

    private function resolvePathFile(string $path): ResponseInterface
    {
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

        return $this->listDirectory($destination, $rootPath);
    }

    private function listDirectory(string $destination, string $rootPath): ResponseInterface
    {
        $directoryIterator = new RecursiveDirectoryIterator(
            $destination,
            FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO,
        );

        $files = [];
        foreach ($directoryIterator as $file) {
            if ($file->getBasename() === '.') {
                continue;
            }

            $filePath = $file->getPathName();
            if ($file->isDir()) {
                $filePath = ($file->getBasename() === '..' ? realpath($filePath) : $filePath) . '/';
            }

            if (!str_starts_with($filePath, $rootPath)) {
                continue;
            }

            $files[] = array_merge(['path' => $this->removeBasePath(
                $rootPath,
                $filePath,
            )], $this->serializeFileInfo($file));
        }

        return $this->responseFactory->createJsonResponse($files);
    }

    private function removeBasePath(string $rootPath, string $path): string|array|null
    {
        return preg_replace('/^' . preg_quote($rootPath, '/') . '/', '', $path, 1);
    }

    private function serializeFileInfo(SplFileInfo $file): array
    {
        return [
            'baseName' => $file->getBasename(),
            'extension' => $file->getExtension(),
            'user' => $this->resolveOwnerInfo((int) $file->getOwner(), 'posix_getpwuid', ['uid', 'gid', 'name']),
            'group' => $this->resolveOwnerInfo((int) $file->getGroup(), 'posix_getgrgid', ['gid', 'name']),
            'size' => $file->getSize(),
            'type' => $file->getType(),
            'permissions' => substr(sprintf('%o', $file->getPerms()), -4),
            'mtime' => $file->getMTime(),
        ];
    }

    private function resolveOwnerInfo(int $id, string $posixFunction, array $fields): array
    {
        if ($id === 0) {
            return ['id' => $id];
        }

        if (!function_exists($posixFunction)) {
            return ['id' => $id];
        }

        $info = $posixFunction($id);

        return $info !== false ? array_intersect_key($info, array_flip($fields)) : ['id' => $id];
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
