<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel;

final readonly class DebuggerIgnoreConfig
{
    /**
     * @param array $requests Patterns for ignored request URLs.
     * @param array $commands Patterns for ignored command names.
     */
    public function __construct(
        public array $requests = [],
        public array $commands = [],
    ) {}
}
