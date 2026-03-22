<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Storage;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\DebuggerIdGenerator;

final class MemoryStorage implements StorageInterface
{
    /**
     * @var CollectorInterface[]
     */
    private array $collectors = [];

    public function __construct(
        private readonly DebuggerIdGenerator $idGenerator,
    ) {}

    public function addCollector(CollectorInterface $collector): void
    {
        $this->collectors[$collector->getId()] = $collector;
    }

    public function read(string $type, ?string $id = null): array
    {
        $result = [];

        // Include data from direct writes
        foreach ($this->entries as $entryId => $entryData) {
            if ($id !== null && $entryId !== $id) {
                continue;
            }
            $result[$entryId] = $entryData[$type] ?? [];
        }

        // Include data from collectors (current session)
        if ($id === null || $id === $this->idGenerator->getId()) {
            if ($type === self::TYPE_SUMMARY) {
                $result[$this->idGenerator->getId()] = [
                    'id' => $this->idGenerator->getId(),
                    'collectors' => array_map(static fn(CollectorInterface $collector) => [
                        'id' => $collector->getId(),
                        'name' => $collector->getName(),
                    ], array_values($this->collectors)),
                ];
            } elseif ($type === self::TYPE_OBJECTS) {
                $collected = $this->getData();
                $result[$this->idGenerator->getId()] = $collected === []
                    ? []
                    : array_merge(...array_values($collected));
            } else {
                $result[$this->idGenerator->getId()] = $this->getData();
            }
        }

        return $result;
    }

    /** @var array<string, array<string, array>> */
    private array $entries = [];

    public function write(string $id, array $summary, array $data, array $objects): void
    {
        $this->entries[$id] = [
            self::TYPE_SUMMARY => $summary,
            self::TYPE_DATA => $data,
            self::TYPE_OBJECTS => $objects,
        ];
    }

    public function getData(): array
    {
        $data = [];

        foreach ($this->collectors as $name => $collector) {
            $data[$name] = $collector->getCollected();
        }

        return $data;
    }

    public function flush(): void
    {
        $this->collectors = [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function clear(): void {}
}
