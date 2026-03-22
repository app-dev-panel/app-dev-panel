<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;

final class RequestInfoAction
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['fixture' => 'request:basic', 'status' => 'ok']);
    }
}
