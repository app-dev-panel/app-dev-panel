<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Assertion;

use AppDevPanel\Testing\Fixture\Expectation;

final class ExpectationEvaluator
{
    private readonly FieldAssertionEvaluator $fieldEvaluator;

    public function __construct()
    {
        $this->fieldEvaluator = new FieldAssertionEvaluator();
    }

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
            'exists' => AssertionResult::pass(sprintf('[%s] collector exists', $collectorName)),
            'not_empty' => $this->assertNotEmpty($collectorName, $data),
            'count_gte' => $this->assertCountGte($collectorName, $data, (int) $expectation->value),
            'field_equals',
            'field_contains',
            'any_field_equals',
            'any_field_contains',
                => $this->fieldEvaluator->evaluate($collectorName, $data, $expectation),
            'summary_has_key', 'summary_gte' => AssertionResult::pass(sprintf(
                '[%s] summary check (deferred)',
                $collectorName,
            )),
            default => AssertionResult::fail(sprintf(
                '[%s] Unknown assertion type: %s',
                $collectorName,
                $expectation->type,
            )),
        };
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
}
