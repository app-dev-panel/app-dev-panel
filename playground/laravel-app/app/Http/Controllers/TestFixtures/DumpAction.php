<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;

final class DumpAction
{
    public function __invoke(): JsonResponse
    {
        dump(['fixture' => 'var-dumper:basic', 'nested' => ['key' => 'value']]);

        return new JsonResponse(['fixture' => 'var-dumper:basic', 'status' => 'ok']);
    }
}
