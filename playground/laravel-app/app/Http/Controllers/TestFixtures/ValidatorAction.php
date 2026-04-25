<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

final class ValidatorAction
{
    public function __invoke(): JsonResponse
    {
        // Passing validation — ValidatorListener captures via after() hook
        Validator::make(['email' => 'user@example.com', 'name' => 'John'], [
            'email' => 'required|email',
            'name' => 'required|string|min:2',
        ])->validate();

        // Failing validation — ValidatorListener captures via after() hook
        $validator = Validator::make(['email' => 'not-an-email', 'name' => ''], [
            'email' => 'required|email',
            'name' => 'required|string|min:2',
        ]);
        $validator->fails();

        return new JsonResponse(['fixture' => 'validator:basic', 'status' => 'ok']);
    }
}
