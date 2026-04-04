<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

final class TestFixtureJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        public readonly int $userId,
        public readonly string $channel,
        public readonly string $subject,
    ) {}

    public function handle(): void
    {
        // No-op: this job exists only to generate queue events for the ADP collector.
    }
}
