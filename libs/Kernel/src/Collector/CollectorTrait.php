<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

trait CollectorTrait
{
    private bool $isActive = false;

    public function startup(): void
    {
        $this->isActive = true;
    }

    public function shutdown(): void
    {
        $this->reset();
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
