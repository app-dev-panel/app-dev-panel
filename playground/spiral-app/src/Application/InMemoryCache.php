<?php

declare(strict_types=1);

namespace App\Application;

use Psr\SimpleCache\CacheInterface;

/**
 * Tiny in-memory PSR-16 cache that lives for one request. Used by the Spiral
 * playground so the ADP `/inspect/api/cache` endpoint has something to inspect.
 */
final class InMemoryCache implements CacheInterface
{
    /** @var array<string, array{value: mixed, expiresAt: int|null}> */
    private array $store = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $this->store)) {
            return $default;
        }
        $entry = $this->store[$key];
        if ($entry['expiresAt'] !== null && $entry['expiresAt'] <= time()) {
            unset($this->store[$key]);
            return $default;
        }
        return $entry['value'];
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->store[$key] = [
            'value' => $value,
            'expiresAt' => $this->expiresAt($ttl),
        ];
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
            $out[$key] = $this->get($key, $default);
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
            $this->delete($key);
        }
        return true;
    }

    public function has(string $key): bool
    {
        return $this->get($key, '__missing__') !== '__missing__';
    }

    private function expiresAt(null|int|\DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }
        if ($ttl instanceof \DateInterval) {
            return new \DateTimeImmutable()->add($ttl)->getTimestamp();
        }
        return time() + $ttl;
    }
}
