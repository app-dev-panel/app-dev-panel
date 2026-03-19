<?php

declare(strict_types=1);

namespace App\actions\testScenarios;

use yii\base\Action;

final class FilesystemAction extends Action
{
    public function run(): array
    {
        $tmpFile = sys_get_temp_dir() . '/adp-test-scenario-' . uniqid() . '.txt';
        file_put_contents($tmpFile, 'ADP filesystem test scenario');
        file_get_contents($tmpFile);
        unlink($tmpFile);

        return ['scenario' => 'filesystem:basic', 'status' => 'ok'];
    }
}
