<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

final class MultiAction
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    /**
     * @return array<string, string>
     */
    public function __invoke(): array
    {
        $this->logger->info('multi-fixture log 1');
        $this->dispatcher->dispatch(new TestFixtureEvent('multi-event 1'));
        $this->logger->warning('multi-fixture log 2');
        $this->dispatcher->dispatch(new TestFixtureEvent('multi-event 2'));
        dump(['from' => 'multi fixture']);

        return ['fixture' => 'multi:logs-and-events', 'status' => 'ok'];
    }
}
