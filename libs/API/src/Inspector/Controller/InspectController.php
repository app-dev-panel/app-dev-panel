<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Controller;

use Alexkart\CurlBuilder\Command;
use FilesystemIterator;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Message;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use RecursiveDirectoryIterator;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;
use Throwable;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Config\ConfigInterface;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Router\UrlMatcherInterface;
use Yiisoft\Translator\CategorySource;
use Yiisoft\VarDumper\VarDumper;
use Yiisoft\Yii\Debug\Api\Debug\Repository\CollectorRepositoryInterface;
use Yiisoft\Yii\Debug\Api\Inspector\ApplicationState;
use Yiisoft\Yii\Debug\Api\Inspector\Database\SchemaProviderInterface;
use Yiisoft\Yii\Debug\Collector\Web\RequestCollector;

class InspectController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private Aliases $aliases,
    ) {
    }

    public function config(ContainerInterface $container, ServerRequestInterface $request): ResponseInterface
    {
        $config = $container->get(ConfigInterface::class);

        $request = $request->getQueryParams();
        $group = $request['group'] ?? 'di';

        $data = $config->get($group);
        ksort($data);

        $response = VarDumper::create($data)->asPrimitives(255);

        return $this->responseFactory->createResponse($response);
    }

    public function getTranslations(ContainerInterface $container): ResponseInterface
    {
        /** @var CategorySource[] $categorySources */
        $categorySources = $container->get('tag@translation.categorySource');

        $params = ApplicationState::$params;

        /** @var string[] $locales */
        $locales = array_keys($params['locale']['locales']);
        if ($locales === []) {
            throw new RuntimeException(
                'Unable to determine list of available locales. ' .
                'Make sure that "$params[\'locale\'][\'locales\']" contains all available locales.'
            );
        }
        $messages = [];
        foreach ($categorySources as $categorySource) {
            $categoryName = $categorySource->getName();

            if (!isset($messages[$categoryName])) {
                $messages[$categoryName] = [];
            }

            try {
                foreach ($locales as $locale) {
                    $messages[$categoryName][$locale] = array_merge(
                        $messages[$categoryName][$locale] ?? [],
                        $categorySource->getMessages($locale)
                    );
                }
            } catch (Throwable) {
            }
        }

        $response = VarDumper::create($messages)->asPrimitives(255);
        return $this->responseFactory->createResponse($response);
    }

    public function putTranslation(ContainerInterface $container, ServerRequestInterface $request): ResponseInterface
    {
        /**
         * @var CategorySource[] $categorySources
         */
        $categorySources = $container->get('tag@translation.categorySource');

        $body = \json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $categoryName = $body['category'] ?? '';
        $locale = $body['locale'] ?? '';
        $translationId = $body['translation'] ?? '';
        $newMessage = $body['message'] ?? '';

        $categorySource = null;
        foreach ($categorySources as $possibleCategorySource) {
            if ($possibleCategorySource->getName() === $categoryName) {
                $categorySource = $possibleCategorySource;
            }
        }
        if ($categorySource === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid category name "%s". Only the following categories are available: "%s"',
                    $categoryName,
                    implode(
                        '", "',
                        array_map(fn (CategorySource $categorySource) => $categorySource->getName(), $categorySources)
                    )
                )
            );
        }
        $messages = $categorySource->getMessages($locale);
        $messages = array_replace_recursive($messages, [
            $translationId => [
                'message' => $newMessage,
            ],
        ]);
        $categorySource->write($locale, $messages);

        $result = [$locale => $messages];
        $response = VarDumper::create($result)->asPrimitives(255);
        return $this->responseFactory->createResponse($response);
    }

    public function params(): ResponseInterface
    {
        $params = ApplicationState::$params;
        ksort($params);

        return $this->responseFactory->createResponse($params);
    }

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

        $rootPath = $this->aliases->get('@root');

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

        if (!is_dir($destination)) {
            return $this->readFile($destination);
        }

        $directoryIterator = new RecursiveDirectoryIterator(
            $destination,
            FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO
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
            $files[] = array_merge(
                [
                    'path' => $path,
                ],
                $this->serializeFileInfo($file)
            );
        }

        return $this->responseFactory->createResponse($files);
    }

    public function classes(): ResponseInterface
    {
        // TODO: how to get params for console or other param groups?
        $classes = [];

        $inspected = [...get_declared_classes(), ...get_declared_interfaces()];
        // TODO: think how to ignore heavy objects
        $patterns = [
            fn (string $class) => !str_starts_with($class, 'ComposerAutoloaderInit'),
            fn (string $class) => !str_starts_with($class, 'Composer\\'),
            fn (string $class) => !str_starts_with($class, 'Yiisoft\\Yii\\Debug\\'),
            fn (string $class) => !str_starts_with($class, 'Yiisoft\\ErrorHandler\\ErrorHandler'),
            fn (string $class) => !str_contains($class, '@anonymous'),
            fn (string $class) => !is_subclass_of($class, Throwable::class),
        ];
        foreach ($patterns as $patternFunction) {
            $inspected = array_filter($inspected, $patternFunction);
        }

        foreach ($inspected as $className) {
            $class = new ReflectionClass($className);

            if ($class->isInternal() || $class->isAbstract() || $class->isAnonymous()) {
                continue;
            }

            $classes[] = $className;
        }
        sort($classes);

        return $this->responseFactory->createResponse($classes);
    }

    public function object(ContainerInterface $container, ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $className = $queryParams['classname'];

        $reflection = new ReflectionClass($className);

        if ($reflection->isInternal()) {
            throw new InvalidArgumentException('Inspector cannot initialize internal classes.');
        }
        if ($reflection->implementsInterface(Throwable::class)) {
            throw new InvalidArgumentException('Inspector cannot initialize exceptions.');
        }

        $variable = $container->get($className);
        $result = VarDumper::create($variable)->asPrimitives(3);

        return $this->responseFactory->createResponse([
            'object' => $result,
            'path' => $reflection->getFileName(),
        ]);
    }

    public function phpinfo(): ResponseInterface
    {
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_get_clean();

        return $this->responseFactory->createResponse($phpinfo);
    }

    public function routes(RouteCollectionInterface $routeCollection): ResponseInterface
    {
        $routes = [];
        foreach ($routeCollection->getRoutes() as $route) {
            $data = $route->__debugInfo();
            $routes[] = [
                'name' => $data['name'],
                'hosts' => $data['hosts'],
                'pattern' => $data['pattern'],
                'methods' => $data['methods'],
                'defaults' => $data['defaults'],
                'override' => $data['override'],
                'middlewares' => $data['middlewareDefinitions'] ?? [],
            ];
        }
        $response = VarDumper::create($routes)->asPrimitives(5);

        return $this->responseFactory->createResponse($response);
    }

    public function checkRoute(
        ServerRequestInterface $request,
        UrlMatcherInterface $matcher,
        ServerRequestFactoryInterface $serverRequestFactory
    ): ResponseInterface {
        $queryParams = $request->getQueryParams();
        $path = $queryParams['route'] ?? null;
        if ($path === null) {
            return $this->responseFactory->createResponse([
                'message' => 'Path is not specified.',
            ], 422);
        }
        $path = trim($path);

        $method = 'GET';
        if (str_contains($path, ' ')) {
            [$possibleMethod, $restPath] = explode(' ', $path, 2);
            if (in_array($possibleMethod, Method::ALL, true)) {
                $method = $possibleMethod;
                $path = $restPath;
            }
        }
        $request = $serverRequestFactory->createServerRequest($method, $path);

        $result = $matcher->match($request);
        if (!$result->isSuccess()) {
            return $this->responseFactory->createResponse([
                'result' => false,
            ]);
        }

        $route = $result->route();
        $reflection = new \ReflectionObject($route);
        $property = $reflection->getProperty('middlewareDefinitions');
        $middlewareDefinitions = $property->getValue($route);
        $action = end($middlewareDefinitions);

        return $this->responseFactory->createResponse([
            'result' => true,
            'action' => $action,
        ]);
    }

    public function getTables(SchemaProviderInterface $schemaProvider): ResponseInterface
    {
        return $this->responseFactory->createResponse($schemaProvider->getTables());
    }

    public function getTable(SchemaProviderInterface $schemaProvider, CurrentRoute $currentRoute): ResponseInterface
    {
        $tableName = $currentRoute->getArgument('name');

        return $this->responseFactory->createResponse($schemaProvider->getTable($tableName));
    }

    public function request(
        ServerRequestInterface $request,
        CollectorRepositoryInterface $collectorRepository
    ): ResponseInterface {
        $request = $request->getQueryParams();
        $debugEntryId = $request['debugEntryId'] ?? null;

        $data = $collectorRepository->getDetail($debugEntryId);
        $rawRequest = $data[RequestCollector::class]['requestRaw'];

        $request = Message::parseRequest($rawRequest);

        $client = new Client();
        $response = $client->send($request);

        $result = VarDumper::create($response)->asPrimitives();

        return $this->responseFactory->createResponse($result);
    }

    public function eventListeners(ContainerInterface $container)
    {
        $config = $container->get(ConfigInterface::class);

        return $this->responseFactory->createResponse([
            'common' => VarDumper::create($config->get('events'))->asPrimitives(),
            // TODO: change events-web to events-web when it will be possible
            'console' => [], //VarDumper::create($config->get('events-web'))->asPrimitives(),
            'web' => VarDumper::create($config->get('events-web'))->asPrimitives(),
        ]);
    }

    public function buildCurl(
        ServerRequestInterface $request,
        CollectorRepositoryInterface $collectorRepository
    ): ResponseInterface {
        $request = $request->getQueryParams();
        $debugEntryId = $request['debugEntryId'] ?? null;


        $data = $collectorRepository->getDetail($debugEntryId);
        $rawRequest = $data[RequestCollector::class]['requestRaw'];

        $request = Message::parseRequest($rawRequest);

        try {
            $output = (new Command())
                ->setRequest($request)
                ->build();
        } catch (Throwable $e) {
            return $this->responseFactory->createResponse([
                'command' => null,
                'exception' => (string)$e,
            ]);
        }

        return $this->responseFactory->createResponse([
            'command' => $output,
        ]);
    }

    private function removeBasePath(string $rootPath, string $path): string|array|null
    {
        return preg_replace(
            '/^' . preg_quote($rootPath, '/') . '/',
            '',
            $path,
            1
        );
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
        return $this->responseFactory->createResponse(
            array_merge(
                $extra,
                [
                    'directory' => $this->removeBasePath($rootPath, dirname($destination)),
                    'content' => file_get_contents($destination),
                    'path' => $this->removeBasePath($rootPath, $destination),
                    'absolutePath' => $destination,
                ],
                $this->serializeFileInfo($file)
            )
        );
    }
}
