<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Service;

use AppDevPanel\Kernel\Helper\Json;

final class FileServiceRegistry implements ServiceRegistryInterface
{
    private readonly string $filePath;

    public function __construct(string $storagePath)
    {
        $this->filePath = rtrim($storagePath, '/') . '/.services.json';
    }

    public function register(ServiceDescriptor $descriptor): void
    {
        $services = $this->load();
        $services[$descriptor->service] = $descriptor->toArray();
        $this->save($services);
    }

    public function deregister(string $service): void
    {
        $services = $this->load();
        unset($services[$service]);
        $this->save($services);
    }

    public function heartbeat(string $service): void
    {
        $services = $this->load();
        if (!array_key_exists($service, $services)) {
            return;
        }
        $services[$service]['lastSeenAt'] = microtime(true);
        $this->save($services);
    }

    public function resolve(string $service): ?ServiceDescriptor
    {
        $services = $this->load();
        if (!array_key_exists($service, $services)) {
            return null;
        }

        return ServiceDescriptor::fromArray($services[$service]);
    }

    public function all(): array
    {
        $result = [];
        foreach ($this->load() as $data) {
            /** @var array $data */
            $result[] = ServiceDescriptor::fromArray($data);
        }

        return $result;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function load(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $content = file_get_contents($this->filePath);
        if ($content === false || $content === '') {
            return [];
        }

        /** @var array<string, array<string, mixed>> */
        return Json::decode($content);
    }

    private function save(array $services): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0o775, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Failed to create directory "%s".', $dir));
            }
        }

        if (file_put_contents($this->filePath, Json::encode($services), LOCK_EX) === false) {
            throw new \RuntimeException(sprintf('Failed to write service registry file "%s".', $this->filePath));
        }
    }
}
