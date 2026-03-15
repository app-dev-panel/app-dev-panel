<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Service;

interface ServiceRegistryInterface
{
    public function register(ServiceDescriptor $descriptor): void;

    public function deregister(string $service): void;

    public function heartbeat(string $service): void;

    public function resolve(string $service): ?ServiceDescriptor;

    /**
     * @return ServiceDescriptor[]
     */
    public function all(): array;
}
