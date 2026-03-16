<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Proxy;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

/**
 * Factory for creating object proxies that implement a given interface.
 * Generates dynamic proxy classes that extend the proxy class AND implement
 * the target interface, with method stubs that delegate to __call().
 */
final class ProxyFactory
{
    private array $classCache = [];

    /**
     * @param string $interface The interface the proxy should implement.
     * @param class-string<AbstractObjectProxy> $proxyClass The proxy class to extend.
     * @param array $constructorArguments Arguments for the proxy constructor.
     */
    public function createObjectProxy(string $interface, string $proxyClass, array $constructorArguments): object
    {
        $interfaces = $this->resolveInterfaces($interface);

        if ($interfaces === []) {
            return new $proxyClass(...$constructorArguments);
        }

        $cacheKey = implode('|', $interfaces) . '::' . $proxyClass;
        if (!isset($this->classCache[$cacheKey])) {
            $this->classCache[$cacheKey] = $this->generateProxyClass($interfaces, $proxyClass);
        }

        $generatedClass = $this->classCache[$cacheKey];
        return new $generatedClass(...$constructorArguments);
    }

    /**
     * @return string[] List of interface names to implement.
     */
    private function resolveInterfaces(string $interface): array
    {
        if (interface_exists($interface)) {
            return [$interface];
        }

        if (class_exists($interface)) {
            $interfaces = new ReflectionClass($interface)->getInterfaceNames();
            return $interfaces !== [] ? $interfaces : [];
        }

        return [];
    }

    /**
     * @param string[] $interfaces
     */
    private function generateProxyClass(array $interfaces, string $proxyClass): string
    {
        $className = 'ADP_Proxy_' . md5(implode('|', $interfaces) . $proxyClass);

        if (class_exists($className, false)) {
            return $className;
        }

        $interfacesFqn = implode(', ', array_map(static fn(string $i): string => '\\' . ltrim($i, '\\'), $interfaces));
        $proxyClassFqn = '\\' . ltrim($proxyClass, '\\');

        $methods = '';
        foreach ($interfaces as $iface) {
            $methods .= $this->generateMethodStubs($iface, $proxyClass);
        }

        eval("class {$className} extends {$proxyClassFqn} implements {$interfacesFqn} {\n{$methods}}");

        return $className;
    }

    private function generateMethodStubs(string $interface, string $proxyClass): string
    {
        $reflection = new ReflectionClass($interface);
        $proxyReflection = new ReflectionClass($proxyClass);
        $stubs = '';

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip methods already defined in the proxy class hierarchy
            if ($proxyReflection->hasMethod($method->getName())) {
                continue;
            }

            $stubs .= $this->generateMethodStub($method);
        }

        return $stubs;
    }

    private function generateMethodStub(ReflectionMethod $method): string
    {
        $name = $method->getName();
        $params = [];
        $args = [];

        foreach ($method->getParameters() as $param) {
            $params[] = $this->renderParameter($param);
            $args[] = ($param->isVariadic() ? '...' : '') . '$' . $param->getName();
        }

        $paramsStr = implode(', ', $params);
        $argsStr = implode(', ', $args);
        $returnType = $this->renderReturnType($method);

        $isVoid =
            $method->hasReturnType()
            && $method->getReturnType() instanceof ReflectionNamedType
            && $method->getReturnType()->getName() === 'void';

        $body = $isVoid
            ? "\$this->__call('{$name}', [{$argsStr}]);"
            : "return \$this->__call('{$name}', [{$argsStr}]);";

        return "    public function {$name}({$paramsStr}){$returnType} { {$body} }\n";
    }

    private function renderParameter(ReflectionParameter $param): string
    {
        $parts = [];

        if ($param->hasType()) {
            $parts[] = $this->renderType($param->getType());
        }

        $variadic = $param->isVariadic() ? '...' : '';
        $ref = $param->isPassedByReference() ? '&' : '';
        $parts[] = $variadic . $ref . '$' . $param->getName();

        if (!$param->isVariadic() && $param->isDefaultValueAvailable()) {
            $parts[] = '= ' . var_export($param->getDefaultValue(), true);
        } elseif (!$param->isVariadic() && $param->isOptional()) {
            $parts[] = '= null';
        }

        return implode(' ', $parts);
    }

    private function renderReturnType(ReflectionMethod $method): string
    {
        if (!$method->hasReturnType()) {
            return '';
        }

        return ': ' . $this->renderType($method->getReturnType());
    }

    private function renderType(ReflectionType $type): string
    {
        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map($this->renderType(...), $type->getTypes()));
        }

        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();
            if (!$type->isBuiltin()) {
                $name = '\\' . $name;
            }
            return ($type->allowsNull() && $name !== 'mixed' ? '?' : '') . $name;
        }

        return (string) $type;
    }
}
