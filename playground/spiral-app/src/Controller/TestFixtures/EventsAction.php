<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Psr\EventDispatcher\EventDispatcherInterface;

final class EventsAction
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $event1 = $this->dispatcher->dispatch(new TestFixtureEvent('first event'));
        $event2 = $this->dispatcher->dispatch(new TestFixtureEvent('second event'));
        $event3 = $this->dispatcher->dispatch(new TestFixtureEvent('third event'));

        return [
            'fixture' => 'events:basic',
            'status' => 'ok',
            'dispatched' => 3,
            'handled' => [$event1->handled, $event2->handled, $event3->handled],
        ];
    }
}
