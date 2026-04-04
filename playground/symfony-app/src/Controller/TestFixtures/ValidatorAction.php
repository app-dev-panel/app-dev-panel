<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/test/fixtures/validator', name: 'test_validator', methods: ['GET'])]
final readonly class ValidatorAction
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {}

    public function __invoke(): JsonResponse
    {
        // Validate using Symfony's Validator — the SymfonyValidatorProxy intercepts
        // these calls and feeds validation results to ValidatorCollector.
        $constraints = new Assert\Collection([
            'email' => [new Assert\NotBlank(), new Assert\Email()],
            'name' => [new Assert\NotBlank(), new Assert\Length(min: 2)],
        ]);

        // Valid data
        $validViolations = $this->validator->validate(['email' => 'user@example.com', 'name' => 'John'], $constraints);

        // Invalid data
        $invalidViolations = $this->validator->validate(['email' => 'not-an-email', 'name' => ''], $constraints);

        return new JsonResponse([
            'fixture' => 'validator:basic',
            'status' => 'ok',
            'validErrors' => $validViolations->count(),
            'invalidErrors' => $invalidViolations->count(),
        ]);
    }
}
