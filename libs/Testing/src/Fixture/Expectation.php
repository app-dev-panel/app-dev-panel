<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Fixture;

/**
 * An expectation about collector data after a fixture runs.
 */
final readonly class Expectation
{
    /**
     * @param string $type Assertion type: 'exists', 'count_gte', 'contains', 'field_equals', 'not_empty'
     * @param string|null $path Dot-notation path within collector data (e.g., '0.level' for first log entry's level)
     * @param mixed $value Expected value (meaning depends on assertion type)
     */
    public function __construct(
        public string $type,
        public ?string $path = null,
        public mixed $value = null,
    ) {}

    /**
     * Collector data must not be empty.
     */
    public static function notEmpty(): self
    {
        return new self('not_empty');
    }

    /**
     * Collector data must exist (key present in response).
     */
    public static function exists(): self
    {
        return new self('exists');
    }

    /**
     * Collector data array count must be >= $min.
     */
    public static function countGte(int $min): self
    {
        return new self('count_gte', value: $min);
    }

    /**
     * A field at dot-path must equal expected value.
     */
    public static function fieldEquals(string $path, mixed $value): self
    {
        return new self('field_equals', $path, $value);
    }

    /**
     * A field at dot-path must contain the substring.
     */
    public static function fieldContains(string $path, string $substring): self
    {
        return new self('field_contains', $path, $substring);
    }

    /**
     * Summary data must contain the given key.
     */
    public static function summaryHasKey(string $key): self
    {
        return new self('summary_has_key', value: $key);
    }

    /**
     * Summary data at path must be >= value.
     */
    public static function summaryGte(string $path, int $min): self
    {
        return new self('summary_gte', $path, $min);
    }
}
