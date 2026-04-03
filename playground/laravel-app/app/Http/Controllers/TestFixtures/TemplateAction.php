<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;

final class TemplateAction
{
    public function __invoke(): JsonResponse
    {
        view('test.fixture-template', ['title' => 'Home', 'user' => 'admin'])->render();

        return new JsonResponse(['fixture' => 'template:basic', 'status' => 'ok']);
    }
}
