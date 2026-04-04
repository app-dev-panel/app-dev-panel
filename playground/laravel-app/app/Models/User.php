<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Auth\GenericUser;

/**
 * Minimal user model for ADP test fixtures (no database needed).
 */
final class User extends GenericUser
{
    public static function fixture(string $email = 'admin@example.com', int $id = 1): self
    {
        return new self([
            'id' => $id,
            'name' => 'Admin',
            'email' => $email,
        ]);
    }
}
