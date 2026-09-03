<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Storage;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\DebugServer\Broadcaster;
use AppDevPanel\Kernel\DebugServer\BroadcasterInterface;
use AppDevPanel\Kernel\DebugServer\Connection;

/**
 * Storage decorator that broadcasts entry-created notifications via UDP.
 *
 * When a debug entry is written (via flush or write), broadcasts a
 * MESSAGE_TYPE_ENTRY_CREATED message so SSE listeners and CLI debug
 * servers are notified immediately without polling.
 *
 * On `flush()` the broadcast carries the id of the entry that was just
 * flushed. Pass the same {@see DebuggerIdGenerator} the decorated storage
 * uses so the id is known without re-reading the storage; without it the
 * decorator falls back to the newest summary (storages return summaries in
 * ascending creation order, so that is the last key).
 */
final class BroadcastingStorage implements StorageInterface
{
    private readonly BroadcasterInterface $broadcaster;

    public function __construct(
        private readonly StorageInterface $decorated,
        ?BroadcasterInterface $broadcaster = null,
        private readonly ?DebuggerIdGenerator $idGenerator = null,
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
        // Capture before delegating: an adapter may reset the generator right after flush.
        $id = $this->idGenerator?->getId();

        $this->decorated->flush();

        if ($id === null) {
            $id = $this->findNewestEntryId();
        }

        if ($id !== null) {
            $this->broadcastEntryCreated($id);
        }
    }

    public function clear(): void
    {
        $this->decorated->clear();
    }

    /**
     * Fallback when no id generator is available: storages list summaries in
     * ascending creation order, so the entry just flushed is the last one.
     */
    private function findNewestEntryId(): ?string
    {
        $summaries = $this->decorated->read(StorageInterface::TYPE_SUMMARY, null);
        if ($summaries === []) {
            return null;
        }

        $latestKey = array_key_last($summaries);

        return $latestKey === null ? null : (string) $latestKey;
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
