<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

final class CoverageAction extends Action
{
    public function run(): array
    {
        // Execute some code to generate coverage data if the collector is active
        $data = array_map(fn(int $i) => $i * $i, range(1, 100));
        $sum = array_sum($data);

        return ['fixture' => 'coverage:basic', 'status' => 'ok', 'sum' => $sum];
    }
}
