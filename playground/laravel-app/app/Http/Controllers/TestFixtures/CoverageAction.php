<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;

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
