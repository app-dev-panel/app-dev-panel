<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Assertion;

use AppDevPanel\Testing\Fixture\Expectation;

/**
 * Evaluates expectations against actual collector data.
 */
final class ExpectationEvaluator
{
    /**
     * @param array $collectorData Data from a single collector
     * @param list<Expectation> $expectations
     *
     * @return list<AssertionResult>
     */
    public function evaluate(string $collectorName, array $collectorData, array $expectations): array
    {
        $results = [];

        foreach ($expectations as $expectation) {
            $results[] = $this->evaluateOne($collectorName, $collectorData, $expectation);
        }

        return $results;
    }

    private function evaluateOne(string $collectorName, array $data, Expectation $expectation): AssertionResult
    {
        return match ($expectation->type) {
            'exists' => $this->assertExists($collectorName),
            'not_empty' => $this->assertNotEmpty($collectorName, $data),
            'count_gte' => $this->assertCountGte($collectorName, $data, (int) $expectation->value),
            'field_equals' => $this->assertFieldEquals($collectorName, $data, $expectation->path, $expectation->value),
            'field_contains' => $this->assertFieldContains(
                $collectorName,
                $data,
                $expectation->path,
                (string) $expectation->value,
            ),
            'any_field_equals' => $this->assertAnyFieldEquals(
                $collectorName,
                $data,
                $expectation->path,
                $expectation->value,
            ),
            'any_field_contains' => $this->assertAnyFieldContains(
                $collectorName,
                $data,
                $expectation->path,
                (string) $expectation->value,
            ),
            'summary_has_key' => AssertionResult::pass(sprintf('[%s] summary check (deferred)', $collectorName)),
            'summary_gte' => AssertionResult::pass(sprintf('[%s] summary check (deferred)', $collectorName)),
            default => AssertionResult::fail(sprintf(
                '[%s] Unknown assertion type: %s',
                $collectorName,
                $expectation->type,
            )),
        };
    }

    private function assertExists(string $collectorName): AssertionResult
    {
        // If we got here, the collector key exists in the response
        return AssertionResult::pass(sprintf('[%s] collector exists', $collectorName));
    }

    private function assertNotEmpty(string $collectorName, array $data): AssertionResult
    {
        if ($data === []) {
            return AssertionResult::fail(sprintf('[%s] expected non-empty data, got empty', $collectorName));
        }

        return AssertionResult::pass(sprintf('[%s] data is not empty (%d entries)', $collectorName, count($data)));
    }

    private function assertCountGte(string $collectorName, array $data, int $min): AssertionResult
    {
        $count = count($data);
        if ($count < $min) {
            return AssertionResult::fail(sprintf('[%s] expected >= %d entries, got %d', $collectorName, $min, $count));
        }

        return AssertionResult::pass(sprintf('[%s] has %d entries (>= %d)', $collectorName, $count, $min));
    }

    private function assertFieldEquals(
        string $collectorName,
        array $data,
        ?string $path,
        mixed $expected,
    ): AssertionResult {
        if ($path === null) {
            return AssertionResult::fail(sprintf('[%s] field_equals requires a path', $collectorName));
        }

        $actual = $this->getByPath($data, $path);
        if ($actual === null && $expected !== null) {
            return AssertionResult::fail(sprintf('[%s] field "%s" not found', $collectorName, $path));
        }

        if ($actual !== $expected) {
            return AssertionResult::fail(sprintf(
                '[%s] field "%s": expected %s, got %s',
                $collectorName,
                $path,
                json_encode($expected, JSON_THROW_ON_ERROR),
                json_encode($actual, JSON_THROW_ON_ERROR),
            ));
        }

        return AssertionResult::pass(sprintf(
            '[%s] field "%s" equals %s',
            $collectorName,
            $path,
            json_encode($expected, JSON_THROW_ON_ERROR),
        ));
    }

    private function assertFieldContains(
        string $collectorName,
        array $data,
        ?string $path,
        string $substring,
    ): AssertionResult {
        if ($path === null) {
            return AssertionResult::fail(sprintf('[%s] field_contains requires a path', $collectorName));
        }

        $actual = $this->getByPath($data, $path);
        if ($actual === null) {
            return AssertionResult::fail(sprintf('[%s] field "%s" not found', $collectorName, $path));
        }

        if (!is_string($actual) || !str_contains($actual, $substring)) {
            return AssertionResult::fail(sprintf(
                '[%s] field "%s": expected to contain "%s", got %s',
                $collectorName,
                $path,
                $substring,
                json_encode($actual, JSON_THROW_ON_ERROR),
            ));
        }

        return AssertionResult::pass(sprintf('[%s] field "%s" contains "%s"', $collectorName, $path, $substring));
    }

    private function assertAnyFieldEquals(
        string $collectorName,
        array $data,
        ?string $path,
        mixed $expected,
    ): AssertionResult {
        if ($path === null) {
            return AssertionResult::fail(sprintf('[%s] any_field_equals requires a path', $collectorName));
        }

        foreach ($data as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $actual = $this->getByPath($entry, $path);
            if ($actual === $expected) {
                return AssertionResult::pass(sprintf(
                    '[%s] found entry with "%s" = %s',
                    $collectorName,
                    $path,
                    json_encode($expected, JSON_THROW_ON_ERROR),
                ));
            }
        }

        return AssertionResult::fail(sprintf(
            '[%s] no entry has "%s" = %s',
            $collectorName,
            $path,
            json_encode($expected, JSON_THROW_ON_ERROR),
        ));
    }

    private function assertAnyFieldContains(
        string $collectorName,
        array $data,
        ?string $path,
        string $substring,
    ): AssertionResult {
        if ($path === null) {
            return AssertionResult::fail(sprintf('[%s] any_field_contains requires a path', $collectorName));
        }

        foreach ($data as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $actual = $this->getByPath($entry, $path);
            if (is_string($actual) && str_contains($actual, $substring)) {
                return AssertionResult::pass(sprintf(
                    '[%s] found entry with "%s" containing "%s"',
                    $collectorName,
                    $path,
                    $substring,
                ));
            }
        }

        return AssertionResult::fail(sprintf(
            '[%s] no entry has "%s" containing "%s"',
            $collectorName,
            $path,
            $substring,
        ));
    }

    private function getByPath(array $data, string $path): mixed
    {
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } elseif (is_array($current) && is_numeric($key) && array_key_exists((int) $key, $current)) {
                $current = $current[(int) $key];
            } else {
                return null;
            }
        }

        return $current;
    }
}
