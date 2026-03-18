<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Assertion;

final readonly class AssertionResult
{
    public function __construct(
        public bool $passed,
        public string $message,
    ) {}

    public static function pass(string $message): self
    {
        return new self(true, $message);
    }

    public static function fail(string $message): self
    {
        return new self(false, $message);
    }
}
