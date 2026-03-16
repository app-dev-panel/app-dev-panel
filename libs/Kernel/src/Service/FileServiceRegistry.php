<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Service;

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
        if (!isset($services[$service])) {
            return;
        }
        $services[$service]['lastSeenAt'] = microtime(true);
        $this->save($services);
    }

    public function resolve(string $service): ?ServiceDescriptor
    {
        $services = $this->load();
        if (!isset($services[$service])) {
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
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    private function save(array $services): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(
            $this->filePath,
            json_encode($services, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            LOCK_EX,
        );
    }
}
