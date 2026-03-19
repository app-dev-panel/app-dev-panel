<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;
use yii\base\Application;
use yii\base\Event;

final class EventsAction extends Action
{
    public function run(): array
    {
        $event = new Event();
        $event->data = ['fixture' => 'events:basic'];
        Event::trigger(Application::class, 'adp.test.scenario', $event);

        return ['fixture' => 'events:basic', 'status' => 'ok'];
    }
}
