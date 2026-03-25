<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/coverage', name: 'test_coverage', methods: ['GET'])]
final class CoverageAction
{
    public function __invoke(): JsonResponse
    {
        // Execute some code to generate coverage data if the collector is active
        $data = array_map(fn(int $i) => $i * $i, range(1, 100));
        $sum = array_sum($data);

        return new JsonResponse(['fixture' => 'coverage:basic', 'status' => 'ok', 'sum' => $sum]);
    }
}
