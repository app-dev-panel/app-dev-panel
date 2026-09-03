<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\NullPathMapper;
use AppDevPanel\Api\PathMapperInterface;
use AppDevPanel\Api\PathResolver;
use AppDevPanel\Api\PathResolverInterface;
use AppDevPanel\Api\Security\ClassNameValidator;
use FilesystemIterator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RecursiveDirectoryIterator;
use ReflectionClass;
use SplFileInfo;

final class FileController
{
    private const string ACCESS_DENIED = 'Access denied: path is outside the project root.';

    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly PathResolverInterface $pathResolver,
        private readonly PathMapperInterface $pathMapper = new NullPathMapper(),
    ) {}

    public function files(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams() + ['class' => '', 'method' => '', 'path' => ''];

        // Only a syntactically valid FQCN may trigger the autoloader (see ClassNameValidator).
        if ($params['class'] !== '' && ClassNameValidator::classExists($params['class'])) {
            return $this->resolveClassFile($params['class'], (string) $params['method']);
        }

        return $this->resolvePathFile((string) $params['path']);
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

        return $this->readClassFile($destination, $extra);
    }

    private function resolvePathFile(string $path): ResponseInterface
    {
        $rootPath = PathResolver::canonical($this->pathResolver->getRootPath());
        $mappedPath = $this->pathMapper->mapToRemote($path);
        $relative = preg_replace('/^' . preg_quote($rootPath, '/') . '/', '', $mappedPath, 1);
        $relative = '/' . ltrim((string) $relative, '/');
        $destination = realpath($rootPath . $relative);

        if ($destination === false) {
            return $this->responseFactory->createJsonResponse([
                'message' => sprintf('Destination "%s" does not exist', $path),
            ], 404);
        }

        if (!PathResolver::isInside($rootPath, $destination)) {
            return $this->responseFactory->createJsonResponse(['message' => self::ACCESS_DENIED], 403);
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

        $parentDirectory = dirname($destination);
        $parentEntry = PathResolver::isInside($rootPath, $parentDirectory)
            ? [array_merge(
                ['path' => PathResolver::stripPrefix($rootPath, $parentDirectory . '/')],
                $this->serializeFileInfo(new SplFileInfo($parentDirectory)),
                ['baseName' => '..'],
            )]
            : [];

        $files = [];
        foreach ($directoryIterator as $file) {
            // Symlinks pointing outside the root are hidden from the listing.
            if (!PathResolver::isInside($rootPath, $file->getPathname())) {
                continue;
            }

            $filePath = $file->isDir() ? $file->getPathname() . '/' : $file->getPathname();

            $files[] = array_merge(['path' => PathResolver::stripPrefix(
                $rootPath,
                $filePath,
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
        $rootPath = PathResolver::canonical($this->pathResolver->getRootPath());

        if (!PathResolver::isInside($rootPath, $destination)) {
            return $this->responseFactory->createJsonResponse(['message' => self::ACCESS_DENIED], 403);
        }

        $file = new SplFileInfo($destination);
        return $this->responseFactory->createJsonResponse(array_merge(
            $extra,
            [
                'directory' => PathResolver::stripPrefix($rootPath, dirname($destination)),
                'content' => file_get_contents($destination),
                'path' => PathResolver::stripPrefix($rootPath, $destination),
                'absolutePath' => $this->pathMapper->mapToLocal($destination),
            ],
            $this->serializeFileInfo($file),
        ));
    }

    /**
     * Read a file resolved from a class name. No root-path restriction — if the class
     * is loaded by PHP, its source is trusted and always readable.
     */
    private function readClassFile(string $destination, array $extra = []): ResponseInterface
    {
        $rootPath = PathResolver::canonical($this->pathResolver->getRootPath());
        $insideRoot = PathResolver::isInside($rootPath, $destination);

        $file = new SplFileInfo($destination);
        return $this->responseFactory->createJsonResponse(array_merge(
            $extra,
            [
                'directory' => $insideRoot
                    ? PathResolver::stripPrefix($rootPath, dirname($destination))
                    : dirname($destination),
                'content' => file_get_contents($destination),
                'path' => $insideRoot ? PathResolver::stripPrefix($rootPath, $destination) : $destination,
                'insideRoot' => $insideRoot,
                'absolutePath' => $this->pathMapper->mapToLocal($destination),
            ],
            $this->serializeFileInfo($file),
        ));
    }
}
