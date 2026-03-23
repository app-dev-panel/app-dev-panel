<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Assertion;

use AppDevPanel\Testing\Fixture\Expectation;

final class FieldAssertionEvaluator
{
    private readonly PathResolver $pathResolver;
    private readonly CollectionFieldEvaluator $collectionEvaluator;

    public function __construct()
    {
        $this->pathResolver = new PathResolver();
        $this->collectionEvaluator = new CollectionFieldEvaluator();
    }

    public function evaluate(string $collectorName, array $data, Expectation $expectation): AssertionResult
    {
        return match ($expectation->type) {
            'field_equals' => $this->assertFieldEquals($collectorName, $data, $expectation->path, $expectation->value),
            'field_contains' => $this->assertFieldContains(
                $collectorName,
                $data,
                $expectation->path,
                (string) $expectation->value,
            ),
            'any_field_equals' => $this->collectionEvaluator->assertAnyFieldEquals(
                $collectorName,
                $data,
                $expectation->path,
                $expectation->value,
            ),
            'any_field_contains' => $this->collectionEvaluator->assertAnyFieldContains(
                $collectorName,
                $data,
                $expectation->path,
                (string) $expectation->value,
            ),
            default => AssertionResult::fail(sprintf(
                '[%s] Unknown field assertion type: %s',
                $collectorName,
                $expectation->type,
            )),
        };
    }

    private function assertFieldEquals(
        string $collectorName,
        array $data,
        ?string $path,
        mixed $expected,
    ): AssertionResult {
        $pathCheck = $this->pathResolver->requirePath($collectorName, 'field_equals', $path);
        if ($pathCheck !== null) {
            return $pathCheck;
        }

        $actual = $this->pathResolver->resolve($data, $path);
        if ($actual === null && $expected !== null) {
            return $this->pathResolver->fieldNotFound($collectorName, $path);
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
        $pathCheck = $this->pathResolver->requirePath($collectorName, 'field_contains', $path);
        if ($pathCheck !== null) {
            return $pathCheck;
        }

        $actual = $this->pathResolver->resolve($data, $path);
        if ($actual === null) {
            return $this->pathResolver->fieldNotFound($collectorName, $path);
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
}
