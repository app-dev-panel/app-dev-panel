<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use App\Jobs\TestFixtureFailingJob;
use App\Jobs\TestFixtureJob;
use Illuminate\Http\JsonResponse;

final class QueueAction
{
    public function __invoke(): JsonResponse
    {
        // Successful job — triggers JobProcessing + JobProcessed events
        TestFixtureJob::dispatchSync(userId: 42, channel: 'email', subject: 'Welcome to ADP');

        // Failing job — triggers JobProcessing + JobFailed events
        try {
            TestFixtureFailingJob::dispatchSync(orderId: 'ORD-12345', amount: 99.99);
        } catch (\Throwable) { // @mago-expect no-empty-catch-clause — expected failure, QueueListener captures JobFailed
        }

        return new JsonResponse(['fixture' => 'queue:basic', 'status' => 'ok']);
    }
}
