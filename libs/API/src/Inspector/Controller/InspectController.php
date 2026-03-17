<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use Throwable;
use Yiisoft\VarDumper\VarDumper;

final class InspectController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly ContainerInterface $container,
        private readonly array $params = [],
    ) {}

    public function config(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $group = $queryParams['group'] ?? 'di';

        if (!$this->container->has('config')) {
            return $this->responseFactory->createJsonResponse([
                'error' => 'Config inspection requires framework integration.',
            ], 501);
        }

        $config = $this->container->get('config');
        $data = $config->get($group);
        ksort($data);

        $response = VarDumper::create($data)->asPrimitives(255);

        return $this->responseFactory->createJsonResponse($response);
    }

    public function params(ServerRequestInterface $request): ResponseInterface
    {
        $params = $this->params;
        ksort($params);

        return $this->responseFactory->createJsonResponse($params);
    }

    public function classes(ServerRequestInterface $request): ResponseInterface
    {
        $classes = [];

        $inspected = [...get_declared_classes(), ...get_declared_interfaces()];
        $patterns = [
            static fn(string $class) => !str_starts_with($class, 'ComposerAutoloaderInit'),
            static fn(string $class) => !str_starts_with($class, 'Composer\\'),
            static fn(string $class) => !str_starts_with($class, 'AppDevPanel\\'),
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

        return $this->responseFactory->createJsonResponse($classes);
    }

    public function object(ServerRequestInterface $request): ResponseInterface
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

        if (!$this->container->has($className)) {
            throw new InvalidArgumentException(sprintf(
                'Class "%s" is not registered in the DI container.',
                $className,
            ));
        }

        $variable = $this->container->get($className);
        $result = VarDumper::create($variable)->asPrimitives(3);

        return $this->responseFactory->createJsonResponse([
            'object' => $result,
            'path' => $reflection->getFileName(),
        ]);
    }

    public function phpinfo(ServerRequestInterface $request): ResponseInterface
    {
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_get_clean();

        return $this->responseFactory->createJsonResponse($phpinfo);
    }

    public function eventListeners(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->container->has('config')) {
            return $this->responseFactory->createJsonResponse([
                'error' => 'Event listener inspection requires framework integration.',
            ], 501);
        }

        $config = $this->container->get('config');

        return $this->responseFactory->createJsonResponse([
            'common' => VarDumper::create($config->get('events'))->asPrimitives(),
            'console' => [],
            'web' => VarDumper::create($config->get('events-web'))->asPrimitives(),
        ]);
    }
}
