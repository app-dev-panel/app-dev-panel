<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector;

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

    public function getName(): string
    {
        return self::class;
    }

    private function reset(): void
    {
    }

    private function isActive(): bool
    {
        return $this->isActive;
    }
}
