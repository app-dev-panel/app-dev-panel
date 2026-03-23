<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel;

/**
 * Delegates magic __get, __set, __call to $this->decorated.
 *
 * Classes using this trait must declare a $decorated property
 * holding the proxied object instance.
 */
trait ProxyDecoratedCalls
{
    public function __set(string $name, mixed $value): void
    {
        $this->decorated->$name = $value;
    }

    public function __get(string $name)
    {
        return $this->decorated->$name;
    }

    public function __call(string $name, array $arguments)
    {
        return $this->decorated->$name(...$arguments);
    }
}
