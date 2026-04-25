<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Minimal in-memory user for test fixtures.
 */
final readonly class InMemoryUser implements UserInterface
{
    /**
     * @param string[] $roles
     */
    public function __construct(
        private string $identifier,
        private array $roles = ['ROLE_USER'],
    ) {}

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void {}

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }
}
