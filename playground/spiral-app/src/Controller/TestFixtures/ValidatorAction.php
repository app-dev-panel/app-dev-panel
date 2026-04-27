<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\ValidatorCollector;

final class ValidatorAction
{
    public function __construct(
        private readonly ValidatorCollector $validator,
    ) {}

    /**
     * @return array<string, string>
     */
    public function __invoke(): array
    {
        $this->validator->collect(
            ['email' => 'not-an-email'],
            false,
            ['email' => ['Invalid email format']],
            ['email' => ['email']],
        );

        $this->validator->collect(['name' => 'Alice'], true, [], ['name' => ['required', 'min:3']]);

        return ['fixture' => 'validator:basic', 'status' => 'ok'];
    }
}
