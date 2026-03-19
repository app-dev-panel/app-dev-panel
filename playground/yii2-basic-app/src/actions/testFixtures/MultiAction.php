<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;
use yii\base\Application;
use yii\base\Event;

final class MultiAction extends Action
{
    public function run(): array
    {
        \Yii::info('Multi scenario: log entry 1', 'application');
        $event = new Event();
        $event->data = ['fixture' => 'multi:step'];
        Event::trigger(Application::class, 'adp.test.multi', $event);
        \Yii::info('Multi scenario: log entry 2', 'application');

        return ['fixture' => 'multi:logs-and-events', 'status' => 'ok'];
    }
}
