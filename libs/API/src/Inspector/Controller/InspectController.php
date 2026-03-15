<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use Alexkart\CurlBuilder\Command;
use AppDevPanel\Adapter\Yiisoft\Collector\Web\RequestCollector;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Inspector\ApplicationState;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use FilesystemIterator;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Message;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
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

class InspectController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private Aliases $aliases,
        private LoggerInterface $logger,
    ) {}

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
                'Unable to determine list of available locales. '
                . 'Make sure that "$params[\'locale\'][\'locales\']" contains all available locales.',
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
                        $categorySource->getMessages($locale),
                    );
                }
            } catch (Throwable $exception) {
                $this->logger->warning($exception->getMessage(), ['exception' => $exception]);
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

        if (!preg_match('/^[a-zA-Z]{2,3}([_-][a-zA-Z0-9]{2,8})*$/', $locale)) {
            throw new InvalidArgumentException(sprintf('Invalid locale "%s".', $locale));
        }

        $categorySource = null;
        foreach ($categorySources as $possibleCategorySource) {
            if ($possibleCategorySource->getName() !== $categoryName) {
                continue;
            }

            $categorySource = $possibleCategorySource;
        }
        if ($categorySource === null) {
            throw new InvalidArgumentException(sprintf(
                'Invalid category name "%s". Only the following categories are available: "%s"',
                $categoryName,
                implode('", "', array_map(
                    static fn(CategorySource $categorySource) => $categorySource->getName(),
                    $categorySources,
                )),
            ));
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

    public function classes(): ResponseInterface
    {
        // TODO: how to get params for console or other param groups?
        $classes = [];

        $inspected = [...get_declared_classes(), ...get_declared_interfaces()];
        // TODO: think how to ignore heavy objects
        $patterns = [
            static fn(string $class) => !str_starts_with($class, 'ComposerAutoloaderInit'),
            static fn(string $class) => !str_starts_with($class, 'Composer\\'),
            static fn(string $class) => !str_starts_with($class, 'Yiisoft\\Yii\\Debug\\'),
            static fn(string $class) => !str_starts_with($class, 'Yiisoft\\ErrorHandler\\ErrorHandler'),
            static fn(string $class) => !str_contains($class, '@anonymous'),
            static fn(string $class) => !is_subclass_of($class, Throwable::class),
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
        $className = $queryParams['classname'] ?? null;

        if ($className === null || $className === '') {
            throw new InvalidArgumentException('Query parameter "classname" is required.');
        }

        if (!class_exists($className) && !interface_exists($className)) {
            throw new InvalidArgumentException(sprintf('Class "%s" does not exist.', $className));
        }

        $reflection = new ReflectionClass($className);

        if ($reflection->isInternal()) {
            throw new InvalidArgumentException('Inspector cannot initialize internal classes.');
        }
        if ($reflection->implementsInterface(Throwable::class)) {
            throw new InvalidArgumentException('Inspector cannot initialize exceptions.');
        }

        if (!$container->has($className)) {
            throw new InvalidArgumentException(sprintf(
                'Class "%s" is not registered in the DI container.',
                $className,
            ));
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
        ServerRequestFactoryInterface $serverRequestFactory,
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

    public function getTable(
        SchemaProviderInterface $schemaProvider,
        CurrentRoute $currentRoute,
        ServerRequestInterface $request,
    ): ResponseInterface {
        $tableName = $currentRoute->getArgument('name');
        $queryParams = $request->getQueryParams();
        $limit = min((int) ($queryParams['limit'] ?? 1000), 10000);
        $offset = max((int) ($queryParams['offset'] ?? 0), 0);

        return $this->responseFactory->createResponse($schemaProvider->getTable($tableName, $limit, $offset));
    }

    public function request(
        ServerRequestInterface $request,
        CollectorRepositoryInterface $collectorRepository,
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
        CollectorRepositoryInterface $collectorRepository,
    ): ResponseInterface {
        $request = $request->getQueryParams();
        $debugEntryId = $request['debugEntryId'] ?? null;

        $data = $collectorRepository->getDetail($debugEntryId);
        $rawRequest = $data[RequestCollector::class]['requestRaw'];

        $request = Message::parseRequest($rawRequest);

        try {
            $output = new Command()
                ->setRequest($request)
                ->build();
        } catch (Throwable $e) {
            return $this->responseFactory->createResponse([
                'command' => null,
                'exception' => (string) $e,
            ]);
        }

        return $this->responseFactory->createResponse([
            'command' => $output,
        ]);
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
