<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

trait CollectorTrait
{
    private bool $isActive = false;

    public function startup(): void
    {
        $this->reset();
        $this->isActive = true;
    }

    public function shutdown(): void
    {
        // NOTE: `reset()` is intentionally NOT called here. After `shutdown()` the
        // collector is frozen — `collect*()` is gated by `isActive` so no further
        // mutation happens, but `getCollected()` / `getSummary()` must still read
        // the buffer because storage flush runs AFTER shutdown. The buffer is
        // wiped on the next `startup()` (matters for long-running processes that
        // re-use the same collector instance across requests).
        $this->isActive = false;
    }

    public function getId(): string
    {
        return static::class;
    }

    public function getName(): string
    {
        $className = new \ReflectionClass(static::class)->getShortName();
        $name = (string) preg_replace('/Collector$/', '', $className);

        return trim((string) preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $name));
    }

    protected function reset(): void {}

    private function isActive(): bool
    {
        return $this->isActive;
    }
}
