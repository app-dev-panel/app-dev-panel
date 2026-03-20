<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Collector\Validator;

use AppDevPanel\Kernel\Collector\ValidatorCollector;
use Traversable;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\RulesProviderInterface;
use Yiisoft\Validator\ValidationContext;
use Yiisoft\Validator\ValidatorInterface;

final class ValidatorInterfaceProxy implements ValidatorInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly ValidatorCollector $collector,
    ) {}

    public function validate(
        mixed $data,
        callable|iterable|object|string|null $rules = null,
        ?ValidationContext $context = null,
    ): Result {
        $result = $this->validator->validate($data, $rules, $context);

        if ($rules === null && $data instanceof RulesProviderInterface) {
            $rules = (array) $data->getRules();
        }

        if ($rules instanceof Traversable) {
            $rules = iterator_to_array($rules);
        }

        $this->collector->collect($data, $result->isValid(), $result->getErrors(), $rules);

        return $result;
    }
}
