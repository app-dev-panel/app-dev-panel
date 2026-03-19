<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

final class LogsAction extends Action
{
    public function run(): array
    {
        \Yii::info('Test log: info level message', 'application');
        \Yii::warning('Test log: warning level message', 'application');
        \Yii::error('Test log: error level message', 'application');

        return ['fixture' => 'logs:basic', 'status' => 'ok'];
    }
}
