<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;

/**
 * Route data is collected automatically by DebugMiddleware via RouterDataExtractor.
 * No manual collector calls needed — the middleware captures matched route and all routes.
 */
final class RouterAction
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['fixture' => 'router:basic', 'status' => 'ok']);
    }
}
