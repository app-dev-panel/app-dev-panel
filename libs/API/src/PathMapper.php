<?php

declare(strict_types=1);

namespace AppDevPanel\Api;

/**
 * Prefix-based bidirectional path mapper.
 *
 * Rules are tried in order; first matching prefix wins.
 */
final class PathMapper implements PathMapperInterface
{
    /**
     * @param array<string, string> $rules Map of remote prefix => local prefix.
     *                                     Example: ['/app' => '/home/user/project']
     */
    public function __construct(
        private readonly array $rules = [],
    ) {}

    public function mapToLocal(string $path): string
    {
        foreach ($this->rules as $remote => $local) {
            if (str_starts_with($path, $remote)) {
                return $local . substr($path, strlen($remote));
            }
        }

        return $path;
    }

    public function mapToRemote(string $path): string
    {
        foreach ($this->rules as $remote => $local) {
            if (str_starts_with($path, $local)) {
                return $remote . substr($path, strlen($local));
            }
        }

        return $path;
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
