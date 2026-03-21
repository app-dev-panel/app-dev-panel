<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

final class ResetCliAction
{
    public function __invoke(): JsonResponse
    {
        $exitCode = Artisan::call('debug:reset');
        $output = Artisan::output();

        return new JsonResponse([
            'fixture' => 'reset-cli',
            'status' => $exitCode === 0 ? 'ok' : 'error',
            'exitCode' => $exitCode,
            'output' => trim($output),
        ]);
    }
}
