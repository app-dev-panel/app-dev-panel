<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\EventListener;

use AppDevPanel\Kernel\Collector\ValidatorCollector;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Validation\Validator;

/**
 * Hooks into Laravel's Validator factory via resolver() to feed the ValidatorCollector.
 *
 * Every validator created via Validator::make() gets an after() callback that captures
 * validation results (data, rules, pass/fail, errors) and sends them to the collector.
 */
final class ValidatorListener
{
    /** @var \Closure(): ValidatorCollector */
    private \Closure $collectorFactory;

    /**
     * @param \Closure(): ValidatorCollector $collectorFactory
     */
    public function __construct(\Closure $collectorFactory)
    {
        $this->collectorFactory = $collectorFactory;
    }

    public function register(ValidationFactory $validatorFactory): void
    {
        $collectorFactory = $this->collectorFactory;

        /** @var \Illuminate\Validation\Factory $validatorFactory */
        $validatorFactory->resolver(static function (
            $translator,
            array $data,
            array $rules,
            array $messages,
            array $attributes,
        ) use ($collectorFactory): Validator {
            $validator = new Validator($translator, $data, $rules, $messages, $attributes);

            $validator->after(static function (Validator $v) use ($collectorFactory, $data, $rules): void {
                $isValid = $v->messages()->isEmpty();

                $collectorFactory()->collect(
                    value: $data,
                    isValid: $isValid,
                    errors: $isValid ? [] : $v->messages()->toArray(),
                    rules: $rules,
                );
            });

            return $validator;
        });
    }
}
