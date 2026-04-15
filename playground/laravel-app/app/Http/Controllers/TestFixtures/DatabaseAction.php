<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class DatabaseAction
{
    public function __invoke(): JsonResponse
    {
        // Ensure the Inspector test table exists and has seed rows so
        // /inspect/api/table endpoints have something to introspect.
        DB::statement('CREATE TABLE IF NOT EXISTS test_users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)');

        DB::table('test_users')->upsert(
            [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane Doe', 'email' => 'jane@example.com'],
            ],
            ['id'],
            ['name', 'email'],
        );

        $rows = DB::select('SELECT * FROM test_users ORDER BY id');

        return new JsonResponse([
            'fixture' => 'database:basic',
            'status' => 'ok',
            'users' => $rows,
        ]);
    }
}
