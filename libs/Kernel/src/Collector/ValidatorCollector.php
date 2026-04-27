<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Captures validation operations with results.
 *
 * Framework adapters call collect() with normalized data:
 * the validated value, rules applied, success/failure flag, and error list.
 */
final class ValidatorCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<int, array{value: mixed, rules: mixed, result: bool, errors: array}> */
    private array $validations = [];

    public function collect(mixed $value, bool $isValid, array $errors = [], mixed $rules = null): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->validations[] = [
            'value' => $value,
            'rules' => $rules,
            'result' => $isValid,
            'errors' => $errors,
        ];
    }

    public function getCollected(): array
    {
        return $this->validations;
    }

    public function getSummary(): array
    {
        $count = count($this->validations);
        $countValid = count(array_filter($this->validations, static fn(array $data): bool => $data['result']));

        return [
            'validator' => [
                'total' => $count,
                'valid' => $countValid,
                'invalid' => $count - $countValid,
            ],
        ];
    }

    protected function reset(): void
    {
        $this->validations = [];
    }
}
