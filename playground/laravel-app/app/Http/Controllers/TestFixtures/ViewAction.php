<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;

final class ViewAction
{
    public function __invoke(): JsonResponse
    {
        view('test.fixture-template', ['title' => 'Home', 'user' => 'admin'])->render();
        view('test.partials.header', ['siteName' => 'ADP'])->render();
        view('test.partials.footer', ['year' => 2026])->render();

        return new JsonResponse(['fixture' => 'view:basic', 'status' => 'ok']);
    }
}
