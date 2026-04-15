<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

/**
 * Minimal user provider that treats GenericUser-based users as immutable.
 *
 * Used by ADP test fixtures that call Auth::login() without a real database.
 * All lookups return a canned fixture user; updateRememberToken is a no-op
 * because GenericUser has no save() method.
 */
final class ArrayUserProvider implements UserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        return User::fixture(id: is_numeric($identifier) ? (int) $identifier : 1);
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token): void
    {
        // No-op — GenericUser is immutable, nothing to persist.
    }

    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        if (!isset($credentials['email']) || !is_string($credentials['email'])) {
            return null;
        }
        return User::fixture(email: $credentials['email']);
    }

    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        return true;
    }

    public function rehashPasswordIfRequired(
        Authenticatable $user,
        #[\SensitiveParameter]
        array $credentials,
        bool $force = false,
    ): void {
        // No-op — fixtures don't use real password hashing.
    }
}
