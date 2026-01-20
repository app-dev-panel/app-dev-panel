<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug;

final class DebuggerIdGenerator
{
    private string $id;

    public function __construct()
    {
        $this->generateId();
    }

    private function generateId(): void
    {
        $this->id = str_replace('.', '', uniqid('', true));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function reset(): void
    {
        $this->generateId();
    }
}
