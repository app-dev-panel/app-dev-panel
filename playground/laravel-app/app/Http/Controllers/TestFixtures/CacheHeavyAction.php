<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

final class CacheHeavyAction
{
    public function __invoke(): JsonResponse
    {
        for ($i = 0; $i < 100; $i++) {
            $key = sprintf('app:item:%d', $i);
            $value = [
                'id' => $i,
                'title' => sprintf('Item #%d', $i),
                'tags' => ['tag-' . ($i % 5), 'tag-' . ($i % 7)],
                'metadata' => ['created_at' => '2026-01-15T10:00:00Z', 'ttl' => 3600],
            ];

            $operation = $i % 6;

            match ($operation) {
                0 => Cache::put($key, $value, 3600),
                1, 2, 3 => Cache::get($key),
                4 => Cache::forget($key),
                5 => Cache::has($key),
            };
        }

        return new JsonResponse(['fixture' => 'cache:heavy', 'status' => 'ok']);
    }
}
