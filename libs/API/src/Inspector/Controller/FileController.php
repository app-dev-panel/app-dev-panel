<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use FilesystemIterator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RecursiveDirectoryIterator;
use ReflectionClass;
use SplFileInfo;
use Yiisoft\Aliases\Aliases;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final class FileController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private Aliases $aliases,
    ) {}

    public function files(ServerRequestInterface $request): ResponseInterface
    {
        $request = $request->getQueryParams();
        $class = $request['class'] ?? '';
        $method = $request['method'] ?? '';

        if (!empty($class) && class_exists($class)) {
            $reflection = new ReflectionClass($class);
            $destination = $reflection->getFileName();
            if ($method !== '' && $reflection->hasMethod($method)) {
                $reflectionMethod = $reflection->getMethod($method);
                $startLine = $reflectionMethod->getStartLine();
                $endLine = $reflectionMethod->getEndLine();
            }
            if ($destination === false) {
                return $this->responseFactory->createResponse([
                    'message' => sprintf('Cannot find source of class "%s".', $class),
                ], 404);
            }
            return $this->readFile($destination, [
                'startLine' => $startLine ?? null,
                'endLine' => $endLine ?? null,
            ]);
        }

        $path = $request['path'] ?? '';

        $rootPath = realpath($this->aliases->get('@root'));

        $destination = $this->removeBasePath($rootPath, $path);

        if (!str_starts_with($destination, '/')) {
            $destination = '/' . $destination;
        }

        $destination = realpath($rootPath . $destination);

        if ($destination === false) {
            return $this->responseFactory->createResponse([
                'message' => sprintf('Destination "%s" does not exist', $path),
            ], 404);
        }

        if (!str_starts_with($destination, $rootPath)) {
            return $this->responseFactory->createResponse([
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
            /**
             * Check if path is inside the application directory
             */
            if (!str_starts_with($path, $rootPath)) {
                continue;
            }
            $path = $this->removeBasePath($rootPath, $path);
            $files[] = array_merge([
                'path' => $path,
            ], $this->serializeFileInfo($file));
        }

        return $this->responseFactory->createResponse($files);
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
        ];
    }

    private function readFile(string $destination, array $extra = []): DataResponse
    {
        $rootPath = $this->aliases->get('@root');
        $file = new SplFileInfo($destination);
        return $this->responseFactory->createResponse(array_merge(
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
