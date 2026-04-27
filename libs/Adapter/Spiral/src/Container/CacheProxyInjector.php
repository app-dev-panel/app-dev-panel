<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Container;

use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Proxy\Psr16CacheProxy;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use ReflectionClass;
use Spiral\Core\BinderInterface;
use Spiral\Core\Container\InjectorInterface;

/**
 * Spiral container injector that wraps any `Psr\SimpleCache\CacheInterface` resolution
 * with {@see Psr16CacheProxy} so cache operations are forwarded to {@see CacheCollector}.
 *
 * Registered by {@see \AppDevPanel\Adapter\Spiral\Bootloader\AppDevPanelBootloader::boot()}
 * via `$binder->bindInjector(CacheInterface::class, self::class)`. The bootloader also
 * captures the application's original cache through {@see InjectorTrait::setUnderlying()}
 * before replacing the binding — that captured instance becomes the proxy's inner cache.
 *
 * If nothing is bound a no-op array-backed PSR-16 cache is used as the fallback so the
 * injector remains total even when the application has not configured a cache backend.
 *
 * @implements InjectorInterface<CacheInterface>
 */
final class CacheProxyInjector implements InjectorInterface
{
    use InjectorTrait;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly BinderInterface $binder,
        private readonly CacheCollector $collector,
    ) {}

    public function createInjection(ReflectionClass $class, ?string $context = null): CacheInterface
    {
        /** @var CacheInterface $original */
        $original = $this->resolveUnderlying($this->container, $this->binder, CacheInterface::class, self::nullCache());

        return new Psr16CacheProxy($original, $this->collector);
    }

    private static function nullCache(): CacheInterface
    {
        return new class implements CacheInterface {
            /** @var array<string, mixed> */
            private array $store = [];

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->store[$key] ?? $default;
            }

            public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
            {
                $this->store[$key] = $value;
                return true;
            }

            public function delete(string $key): bool
            {
                unset($this->store[$key]);
                return true;
            }

            public function clear(): bool
            {
                $this->store = [];
                return true;
            }

            public function getMultiple(iterable $keys, mixed $default = null): iterable
            {
                $out = [];
                foreach ($keys as $key) {
                    $stringKey = (string) $key;
                    $out[$stringKey] = $this->get($stringKey, $default);
                }
                return $out;
            }

            public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
            {
                foreach ($values as $key => $value) {
                    $this->set((string) $key, $value, $ttl);
                }
                return true;
            }

            public function deleteMultiple(iterable $keys): bool
            {
                foreach ($keys as $key) {
                    $this->delete((string) $key);
                }
                return true;
            }

            public function has(string $key): bool
            {
                return array_key_exists($key, $this->store);
            }
        };
    }
}
