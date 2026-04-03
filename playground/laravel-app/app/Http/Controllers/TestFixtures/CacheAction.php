<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

final class CacheAction
{
    public function __invoke(): JsonResponse
    {
        // set
        Cache::put('user:42', ['id' => 42, 'name' => 'John Doe', 'email' => 'john@example.com'], 3600);

        // get (hit)
        Cache::get('user:42');

        // get (miss)
        Cache::get('user:99');

        // delete
        Cache::forget('user:42');

        return new JsonResponse(['fixture' => 'cache:basic', 'status' => 'ok']);
    }
}
