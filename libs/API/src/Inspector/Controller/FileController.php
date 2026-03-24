<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\NullPathMapper;
use AppDevPanel\Api\PathMapperInterface;
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
        private readonly PathMapperInterface $pathMapper = new NullPathMapper(),
    ) {}

    public function files(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams() + ['class' => '', 'method' => '', 'path' => ''];

        if ($params['class'] !== '' && class_exists($params['class'])) {
            return $this->resolveClassFile($params['class'], $params['method']);
        }

        return $this->resolvePathFile($params['path']);
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
        $mappedPath = $this->pathMapper->mapToRemote($path);
        $relative = preg_replace('/^' . preg_quote($rootPath, '/') . '/', '', $mappedPath, 1);
        $relative = '/' . ltrim($relative, '/');
        $destination = realpath($rootPath . $relative);

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

        return is_dir($destination) ? $this->listDirectory($destination, $rootPath) : $this->readFile($destination);
    }

    private function listDirectory(string $destination, string $rootPath): ResponseInterface
    {
        $directoryIterator = new RecursiveDirectoryIterator(
            $destination,
            FilesystemIterator::KEY_AS_PATHNAME
            | FilesystemIterator::CURRENT_AS_FILEINFO
            | FilesystemIterator::SKIP_DOTS,
        );

        $parentPath = realpath($destination . '/..') . '/';
        $parentEntry = str_starts_with($parentPath, $rootPath)
            ? [array_merge(['path' => preg_replace(
                '/^' . preg_quote($rootPath, '/') . '/',
                '',
                $parentPath,
                1,
            )], $this->serializeFileInfo(new SplFileInfo(dirname($destination))))]
            : [];

        $files = [];
        foreach ($directoryIterator as $file) {
            $filePath = $file->isDir() ? $file->getPathName() . '/' : $file->getPathName();

            if (!str_starts_with($filePath, $rootPath)) {
                continue;
            }

            $files[] = array_merge(['path' => preg_replace(
                '/^' . preg_quote($rootPath, '/') . '/',
                '',
                $filePath,
                1,
            )], $this->serializeFileInfo($file));
        }

        return $this->responseFactory->createJsonResponse(array_merge($parentEntry, $files));
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
        if ($id === 0 || !function_exists($posixFunction)) {
            return ['id' => $id];
        }

        $info = $posixFunction($id);

        return $info !== false ? array_intersect_key($info, array_flip($fields)) : ['id' => $id];
    }

    private function readFile(string $destination, array $extra = []): ResponseInterface
    {
        $rootPath = $this->pathResolver->getRootPath();
        $pattern = '/^' . preg_quote($rootPath, '/') . '/';
        $file = new SplFileInfo($destination);
        return $this->responseFactory->createJsonResponse(array_merge(
            $extra,
            [
                'directory' => preg_replace($pattern, '', dirname($destination), 1),
                'content' => file_get_contents($destination),
                'path' => preg_replace($pattern, '', $destination, 1),
                'absolutePath' => $this->pathMapper->mapToLocal($destination),
            ],
            $this->serializeFileInfo($file),
        ));
    }
}
