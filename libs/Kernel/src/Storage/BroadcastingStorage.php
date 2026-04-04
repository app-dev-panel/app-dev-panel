<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Storage;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\DebugServer\Broadcaster;
use AppDevPanel\Kernel\DebugServer\Connection;

/**
 * Storage decorator that broadcasts entry-created notifications via UDP.
 *
 * When a debug entry is written (via flush or write), broadcasts a
 * MESSAGE_TYPE_ENTRY_CREATED message so SSE listeners and CLI debug
 * servers are notified immediately without polling.
 */
final class BroadcastingStorage implements StorageInterface
{
    private readonly Broadcaster $broadcaster;

    public function __construct(
        private readonly StorageInterface $decorated,
        ?Broadcaster $broadcaster = null,
    ) {
        $this->broadcaster = $broadcaster ?? new Broadcaster();
    }

    public function addCollector(CollectorInterface $collector): void
    {
        $this->decorated->addCollector($collector);
    }

    public function getData(): array
    {
        return $this->decorated->getData();
    }

    public function read(string $type, ?string $id = null): array
    {
        return $this->decorated->read($type, $id);
    }

    public function write(string $id, array $summary, array $data, array $objects): void
    {
        $this->decorated->write($id, $summary, $data, $objects);
        $this->broadcastEntryCreated($id);
    }

    public function flush(): void
    {
        $this->decorated->flush();

        // After flush, broadcast the entry ID
        $summaries = $this->decorated->read(StorageInterface::TYPE_SUMMARY, null);
        if ($summaries !== []) {
            // Latest entry is first
            $latestKey = array_key_first($summaries);
            if ($latestKey !== null) {
                $this->broadcastEntryCreated((string) $latestKey);
            }
        }
    }

    public function clear(): void
    {
        $this->decorated->clear();
    }

    private function broadcastEntryCreated(string $id): void
    {
        try {
            $this->broadcaster->broadcast(Connection::MESSAGE_TYPE_ENTRY_CREATED, $id);
        } catch (\Throwable) {
            // Never let broadcast failure break the app
        }
    }
}
