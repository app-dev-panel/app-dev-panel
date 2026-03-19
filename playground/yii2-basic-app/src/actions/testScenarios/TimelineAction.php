<?php

declare(strict_types=1);

namespace App\actions\testScenarios;

use yii\base\Action;

final class TimelineAction extends Action
{
    public function run(): array
    {
        \Yii::info('Timeline step 1: start', 'application');
        usleep(10_000);
        \Yii::info('Timeline step 2: processing', 'application');
        usleep(10_000);
        \Yii::info('Timeline step 3: done', 'application');

        return ['scenario' => 'timeline:basic', 'status' => 'ok'];
    }
}
