<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Proxy;

use AppDevPanel\Kernel\Collector\ValidatorCollector;
use AppDevPanel\Kernel\ProxyDecoratedCalls;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Decorates Symfony's ValidatorInterface to feed validation results to ValidatorCollector.
 *
 * Intercepts validate() calls, delegates to the real validator, then logs the result.
 */
final class SymfonyValidatorProxy implements ValidatorInterface
{
    use ProxyDecoratedCalls;

    public function __construct(
        private readonly ValidatorInterface $decorated,
        private readonly ValidatorCollector $collector,
    ) {}

    public function validate(
        mixed $value,
        array|object|null $constraints = null,
        array|string|null $groups = null,
    ): ConstraintViolationListInterface {
        $violations = $this->decorated->validate($value, $constraints, $groups);

        $errors = [];
        foreach ($violations as $violation) {
            $path = $violation->getPropertyPath() ?: '_root';
            $errors[$path][] = (string) $violation->getMessage();
        }

        $rules = null;
        if ($constraints !== null) {
            $rules = is_array($constraints)
                ? array_map(static fn(object $c) => $c::class, $constraints)
                : $constraints::class;
        }

        $this->collector->collect(value: $value, isValid: $violations->count() === 0, errors: $errors, rules: $rules);

        return $violations;
    }

    public function validateProperty(
        object $object,
        string $propertyName,
        array|string|null $groups = null,
    ): ConstraintViolationListInterface {
        return $this->decorated->validateProperty($object, $propertyName, $groups);
    }

    public function validatePropertyValue(
        object|string $objectOrClass,
        string $propertyName,
        mixed $value,
        array|string|null $groups = null,
    ): ConstraintViolationListInterface {
        return $this->decorated->validatePropertyValue($objectOrClass, $propertyName, $value, $groups);
    }

    public function startContext(): ExecutionContextInterface
    {
        return $this->decorated->startContext();
    }

    public function inContext(ExecutionContextInterface $context): ValidatorInterface
    {
        return $this->decorated->inContext($context);
    }

    public function getMetadataFor(mixed $value): \Symfony\Component\Validator\Mapping\MetadataInterface
    {
        return $this->decorated->getMetadataFor($value);
    }

    public function hasMetadataFor(mixed $value): bool
    {
        return $this->decorated->hasMetadataFor($value);
    }
}
