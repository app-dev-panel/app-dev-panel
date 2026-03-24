<?php

declare(strict_types=1);

namespace AppDevPanel\Api;

/**
 * Maps file paths between remote (container/VM) and local (host) environments.
 *
 * Used in Docker/Vagrant setups where the application runs in a container
 * with different paths than the developer's host machine.
 */
interface PathMapperInterface
{
    /**
     * Map a remote (container) path to a local (host) path.
     *
     * Used when displaying paths to the developer (stack traces, file links).
     */
    public function mapToLocal(string $path): string;

    /**
     * Map a local (host) path to a remote (container) path.
     *
     * Used when the frontend sends a path that needs to be resolved on the container filesystem.
     */
    public function mapToRemote(string $path): string;

    /**
     * Get the configured mapping rules.
     *
     * @return array<string, string> Map of remote prefix => local prefix.
     */
    public function getRules(): array;
}
