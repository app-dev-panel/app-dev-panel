<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Router;

final class Route
{
    /**
     * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param string $pattern URL pattern with {param} placeholders
     * @param array{0: class-string, 1: string} $handler Controller class and method
     * @param string|null $name Optional route name
     */
    public function __construct(
        public readonly string $method,
        public readonly string $pattern,
        public readonly array $handler,
        public readonly ?string $name = null,
    ) {}

    /**
     * @return array<string, string>|null Extracted parameters or null if no match
     */
    public function match(string $method, string $path): ?array
    {
        if ($this->method !== $method) {
            return null;
        }

        $pattern = $this->pattern;

        // Exact match (no parameters)
        if (!str_contains($pattern, '{')) {
            return $path === $pattern ? [] : null;
        }

        // Build regex from pattern
        // {param+:regex} matches one or more path segments with a custom pattern
        $regex = preg_replace_callback(
            '/\{(\w+)\+:([^}]+)\}/',
            static fn(array $m) => '(?P<' . $m[1] . '>' . $m[2] . ')',
            $pattern,
        );
        // {param+} matches one or more path segments (including slashes),
        // {param} matches a single path segment (no slashes)
        $regex = preg_replace(['/\{(\w+)\+\}/', '/\{(\w+)\}/'], ['(?P<$1>.+)', '(?P<$1>[^/]+)'], $regex);
        if (!is_string($regex)) {
            return null;
        }
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $path, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return null;
    }
}
