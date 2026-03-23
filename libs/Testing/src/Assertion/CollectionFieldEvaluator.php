<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Assertion;

final class CollectionFieldEvaluator
{
    private readonly PathResolver $pathResolver;

    public function __construct()
    {
        $this->pathResolver = new PathResolver();
    }

    public function assertAnyFieldEquals(
        string $collectorName,
        array $data,
        ?string $path,
        mixed $expected,
    ): AssertionResult {
        $pathCheck = $this->pathResolver->requirePath($collectorName, 'any_field_equals', $path);
        if ($pathCheck !== null) {
            return $pathCheck;
        }

        foreach ($data as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $actual = $this->pathResolver->resolve($entry, $path);
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

    public function assertAnyFieldContains(
        string $collectorName,
        array $data,
        ?string $path,
        string $substring,
    ): AssertionResult {
        $pathCheck = $this->pathResolver->requirePath($collectorName, 'any_field_contains', $path);
        if ($pathCheck !== null) {
            return $pathCheck;
        }

        foreach ($data as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $actual = $this->pathResolver->resolve($entry, $path);
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
}
