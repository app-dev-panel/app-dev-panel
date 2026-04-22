<?php

declare(strict_types=1);

namespace App\Auth;

use SensitiveParameter;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Auth\IdentityWithTokenRepositoryInterface;

/**
 * In-memory identity repository backing the playground authorization demo.
 *
 * Three fixed users cover every RBAC role exposed on the Authorization
 * inspector page: `alice` (admin), `bob` (editor), `carol` (reader). The
 * tokens are demo-only; real apps must use opaque, high-entropy values
 * stored outside the codebase.
 */
final class DemoIdentityRepository implements IdentityRepositoryInterface, IdentityWithTokenRepositoryInterface
{
    /**
     * @var list<DemoIdentity>
     */
    private readonly array $identities;

    public function __construct()
    {
        $this->identities = [
            new DemoIdentity('1', self::demoToken('alice'), ['name' => 'Alice', 'role' => 'admin']),
            new DemoIdentity('2', self::demoToken('bob'), ['name' => 'Bob', 'role' => 'editor']),
            new DemoIdentity('3', self::demoToken('carol'), ['name' => 'Carol', 'role' => 'reader']),
        ];
    }

    public function findIdentity(string $id): ?IdentityInterface
    {
        foreach ($this->identities as $identity) {
            if ($identity->getId() === $id) {
                return $identity;
            }
        }

        return null;
    }

    public function findIdentityByToken(#[SensitiveParameter] string $token, ?string $type = null): ?IdentityInterface
    {
        foreach ($this->identities as $identity) {
            if (hash_equals($identity->getToken(), $token)) {
                return $identity;
            }
        }

        return null;
    }

    public static function demoToken(string $user): string
    {
        return $user . '-demo-token';
    }
}
