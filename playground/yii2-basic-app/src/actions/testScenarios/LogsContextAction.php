<?php

declare(strict_types=1);

namespace App\actions\testScenarios;

use yii\base\Action;

final class LogsContextAction extends Action
{
    public function run(): array
    {
        \Yii::info('User action', 'application');

        return ['scenario' => 'logs:context', 'status' => 'ok'];
    }
}
