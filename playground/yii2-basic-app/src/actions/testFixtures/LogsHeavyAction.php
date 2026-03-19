<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

final class LogsHeavyAction extends Action
{
    public function run(): array
    {
        for ($i = 1; $i <= 100; $i++) {
            \Yii::info(sprintf('Heavy log entry #%d', $i), 'application');
        }

        return ['fixture' => 'logs:heavy', 'status' => 'ok', 'count' => 100];
    }
}
