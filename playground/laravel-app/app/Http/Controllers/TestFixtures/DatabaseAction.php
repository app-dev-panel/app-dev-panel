<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class DatabaseAction
{
    public function __invoke(): JsonResponse
    {
        DB::select('SELECT 1 as id, ? as name', ['John Doe']);

        return new JsonResponse(['fixture' => 'database:basic', 'status' => 'ok']);
    }
}
