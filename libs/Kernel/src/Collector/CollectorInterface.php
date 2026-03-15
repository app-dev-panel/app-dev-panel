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
     * Called once at application startup.
     * Any initialization could be done here.
     */
    public function startup(): void;

    /**
     * Called once at application shutdown.
     * Cleanup could be done here.
     */
    public function shutdown(): void;

    /**
     * @return array Data collected.
     */
    public function getCollected(): array;
}
