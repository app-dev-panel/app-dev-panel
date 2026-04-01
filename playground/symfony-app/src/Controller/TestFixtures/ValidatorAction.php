<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\ValidatorCollector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/validator', name: 'test_validator', methods: ['GET'])]
final readonly class ValidatorAction
{
    public function __construct(
        private ValidatorCollector $validatorCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        // Simulate a passing validation
        $this->validatorCollector->collect(
            value: ['email' => 'user@example.com', 'name' => 'John'],
            isValid: true,
            rules: ['email' => 'required|email', 'name' => 'required|string|min:2'],
        );

        // Simulate a failing validation
        $this->validatorCollector->collect(
            value: ['email' => 'not-an-email', 'name' => ''],
            isValid: false,
            errors: [
                'email' => ['The email must be a valid email address.'],
                'name' => ['The name field is required.'],
            ],
            rules: ['email' => 'required|email', 'name' => 'required|string|min:2'],
        );

        return new JsonResponse(['fixture' => 'validator:basic', 'status' => 'ok']);
    }
}
