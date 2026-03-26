<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Scheduler;

use DateTimeImmutable;

/**
 * Minimal cron expression parser supporting: minute hour day month weekday.
 * Supports: numbers, wildcards, ranges (1-5), steps, and lists (1,3,5).
 */
final readonly class CronExpression
{
    /** @var list<string> */
    private array $parts;

    public function __construct(
        public string $expression,
    ) {
        $this->parts = preg_split('/\s+/', trim($expression));
        if (count($this->parts) !== 5) {
            throw new \InvalidArgumentException("Invalid cron expression: {$expression} (expected 5 fields)");
        }
    }

    public function isDue(DateTimeImmutable $now): bool
    {
        return (
            $this->matches((int) $now->format('i'), $this->parts[0], 0, 59)
            && $this->matches((int) $now->format('G'), $this->parts[1], 0, 23)
            && $this->matches((int) $now->format('j'), $this->parts[2], 1, 31)
            && $this->matches((int) $now->format('n'), $this->parts[3], 1, 12)
            && $this->matches((int) $now->format('w'), $this->parts[4], 0, 6)
        );
    }

    public function nextRunAfter(DateTimeImmutable $after): DateTimeImmutable
    {
        $candidate = $after->modify('+1 minute');
        $candidate = $candidate->setTime((int) $candidate->format('G'), (int) $candidate->format('i'), 0);

        $maxIterations = 525_960; // 1 year of minutes
        for ($i = 0; $i < $maxIterations; $i++) {
            if ($this->isDue($candidate)) {
                return $candidate;
            }
            $candidate = $candidate->modify('+1 minute');
        }

        throw new \RuntimeException("Cannot find next run time for: {$this->expression}");
    }

    private function matches(int $value, string $field, int $min, int $max): bool
    {
        if ($field === '*') {
            return true;
        }

        foreach (explode(',', $field) as $part) {
            if (str_contains($part, '/')) {
                [$range, $step] = explode('/', $part, 2);
                $step = (int) $step;
                $start = $range === '*' ? $min : (int) $range;
                if (($value - $start) >= 0 && (($value - $start) % $step) === 0) {
                    return true;
                }
            } elseif (str_contains($part, '-')) {
                [$from, $to] = explode('-', $part, 2);
                if ($value >= (int) $from && $value <= (int) $to) {
                    return true;
                }
            } elseif ((int) $part === $value) {
                return true;
            }
        }

        return false;
    }
}
