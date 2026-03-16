<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Proxy;

/**
 * Base class for object proxies that intercept method calls.
 * Replaces Yiisoft\Proxy\ObjectProxy for framework-agnostic usage.
 */
abstract class AbstractObjectProxy
{
    protected object $instance;

    public function __construct(object $instance)
    {
        $this->instance = $instance;
    }

    public function __call(string $name, array $arguments): mixed
    {
        $timeStart = microtime(true);
        $result = $this->instance->$name(...$arguments);
        return $this->afterCall($name, $arguments, $result, $timeStart);
    }

    public function __get(string $name): mixed
    {
        return $this->instance->$name;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->instance->$name = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->instance->$name);
    }

    public function getInstance(): object
    {
        return $this->instance;
    }

    protected function afterCall(string $methodName, array $arguments, mixed $result, float $timeStart): mixed
    {
        return $result;
    }

    protected function getNewStaticInstance(object $instance): static
    {
        return new static($instance);
    }
}
