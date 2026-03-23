<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Assertion;

final class PathResolver
{
    public function resolve(array $data, string $path): mixed
    {
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } elseif (is_array($current) && is_numeric($key) && array_key_exists((int) $key, $current)) {
                $current = $current[(int) $key];
            } else {
                return null;
            }
        }

        return $current;
    }

    public function requirePath(string $collectorName, string $assertionType, ?string $path): ?AssertionResult
    {
        if ($path === null) {
            return AssertionResult::fail(sprintf('[%s] %s requires a path', $collectorName, $assertionType));
        }

        return null;
    }

    public function fieldNotFound(string $collectorName, string $path): AssertionResult
    {
        return AssertionResult::fail(sprintf('[%s] field "%s" not found', $collectorName, $path));
    }
}
