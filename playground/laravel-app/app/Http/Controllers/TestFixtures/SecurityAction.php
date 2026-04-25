<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class SecurityAction
{
    public function __invoke(): JsonResponse
    {
        $user = User::fixture('admin@example.com');

        // Define gates for access decision testing
        Gate::define('admin-access', static fn($user) => true);
        Gate::define('super-admin-access', static fn($user) => false);

        // Triggers Login event → AuthorizationListener collects user, firewall, auth event
        Auth::login($user, remember: true);

        // Triggers GateEvaluated events → GateListener collects access decisions
        Gate::check('admin-access'); // granted (gate returns true)
        Gate::check('super-admin-access'); // denied (gate returns false)

        // Triggers Logout event → AuthorizationListener collects logout auth event
        Auth::logout();

        return new JsonResponse(['fixture' => 'security:basic', 'status' => 'ok']);
    }
}
