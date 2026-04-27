<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Project;

interface ProjectConfigStorageInterface
{
    public function load(): ProjectConfig;

    public function save(ProjectConfig $config): void;

    /**
     * Absolute path of the directory where the project configuration lives.
     * Surfaced to the UI so users see where to find the committable file.
     */
    public function getConfigDir(): string;
}
