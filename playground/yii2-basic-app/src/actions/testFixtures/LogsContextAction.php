<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

final class LogsContextAction extends Action
{
    public function run(): array
    {
        \Yii::info('User action', 'application');

        return ['fixture' => 'logs:context', 'status' => 'ok'];
    }
}
