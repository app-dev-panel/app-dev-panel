<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel;

/**
 * Interface for handling variable dump output.
 * Replaces Yiisoft\VarDumper\HandlerInterface for framework-agnostic usage.
 */
interface DumpHandlerInterface
{
    public function handle(mixed $variable, int $depth, bool $highlight = false): void;
}
