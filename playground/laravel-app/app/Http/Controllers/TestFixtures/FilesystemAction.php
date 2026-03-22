<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;

final class FilesystemAction
{
    public function __invoke(): JsonResponse
    {
        $tmpFile = sys_get_temp_dir() . '/adp-test-scenario-' . uniqid() . '.txt';
        file_put_contents($tmpFile, 'ADP filesystem test scenario');
        file_get_contents($tmpFile);
        unlink($tmpFile);

        return new JsonResponse(['fixture' => 'filesystem:basic', 'status' => 'ok']);
    }
}
