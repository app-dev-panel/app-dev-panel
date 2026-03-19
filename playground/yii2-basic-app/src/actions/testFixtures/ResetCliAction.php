<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

final class ResetCliAction extends Action
{
    public function run(): array
    {
        $yiiPath = \Yii::getAlias('@app') . '/yii';

        $output = [];
        $exitCode = 0;
        exec(sprintf('php %s debug-reset 2>&1', escapeshellarg($yiiPath)), $output, $exitCode);

        return [
            'fixture' => 'reset-cli',
            'status' => $exitCode === 0 ? 'ok' : 'error',
            'exitCode' => $exitCode,
            'output' => implode("\n", $output),
        ];
    }
}
