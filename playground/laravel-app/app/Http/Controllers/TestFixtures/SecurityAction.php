<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class SecurityAction
{
    public function __invoke(): JsonResponse
    {
        $user = User::fixture('admin@example.com');

        // Triggers Login event → AuthorizationListener collects user, firewall, auth event
        Auth::login($user, remember: true);

        // Triggers Logout event → AuthorizationListener collects logout auth event
        Auth::logout();

        return new JsonResponse(['fixture' => 'security:basic', 'status' => 'ok']);
    }
}
