<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector;

/**
 * Debug data collector responsibility is to collect data during application lifecycle.
 */
interface CollectorInterface
{
    /**
     * @return string Collector's name.
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
