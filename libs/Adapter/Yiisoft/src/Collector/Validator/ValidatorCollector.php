<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Collector\Validator;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use Traversable;
use Yiisoft\Validator\Result;

final class ValidatorCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private array $validations = [];

    public function getCollected(): array
    {
        return $this->validations;
    }

    public function collect(mixed $value, Result $result, callable|iterable|object|string|null $rules = null): void
    {
        if (!$this->isActive()) {
            return;
        }

        if ($rules instanceof Traversable) {
            $rules = iterator_to_array($rules);
        }

        $this->validations[] = [
            'value' => $value,
            'rules' => $rules,
            'result' => $result->isValid(),
            'errors' => $result->getErrors(),
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        $count = count($this->validations);
        $countValid = count(array_filter($this->validations, fn(array $data): bool => (bool) $data['result']));
        $countInvalid = $count - $countValid;

        return [
            'total' => $count,
            'valid' => $countValid,
            'invalid' => $countInvalid,
        ];
    }

    private function reset(): void
    {
        $this->validations = [];
    }
}
