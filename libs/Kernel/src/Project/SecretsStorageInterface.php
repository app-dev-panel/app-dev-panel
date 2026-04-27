<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Project;

interface SecretsStorageInterface
{
    public function load(): SecretsConfig;

    public function save(SecretsConfig $config): void;

    /**
     * Absolute path of the directory the secrets file lives in. Same value
     * as {@see ProjectConfigStorageInterface::getConfigDir()} when the two
     * storages share a directory (the default), surfaced here so consumers
     * never have to thread both interfaces through.
     */
    public function getConfigDir(): string;
}
