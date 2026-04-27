<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Debug data collector responsibility is to collect data during application lifecycle.
 */
interface CollectorInterface
{
    /**
     * @return string Collector's unique identifier (FQCN).
     */
    public function getId(): string;

    /**
     * @return string Human-readable short name for display in the UI.
     */
    public function getName(): string;

    /**
     * Called once at application startup. Implementations must restore the buffer
     * to a pristine state here (matters for long-running processes — RoadRunner,
     * Swoole, ReactPHP — that re-use the same collector instance across requests)
     * and attach external observers (stream wrappers, error handlers, decorated
     * services, profiling drivers).
     */
    public function startup(): void;

    /**
     * Called once at application shutdown, BEFORE the storage flushes data to its
     * backend. Implementations must detach all external observers here so the act
     * of writing the debug payload does not feed itself back into the collector.
     *
     * The collected buffer must NOT be cleared here — `getCollected()` /
     * `getSummary()` are still called after `shutdown()` (during flush) to
     * serialize the snapshot. The buffer is reset in the next `startup()`.
     */
    public function shutdown(): void;

    /**
     * @return array Data collected.
     */
    public function getCollected(): array;
}
