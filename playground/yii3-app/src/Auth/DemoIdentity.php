<?php

declare(strict_types=1);

namespace App\Auth;

use SensitiveParameter;
use Yiisoft\Auth\IdentityInterface;

/**
 * Demo identity for the playground authorization showcase.
 *
 * Carries a plain-array "attributes" payload and an opaque API token so
 * {@see DemoIdentityRepository} can resolve the same user by either the
 * identifier or the bearer token.
 */
final class DemoIdentity implements IdentityInterface
{
    /**
     * @param array<string, scalar|null> $attributes
     */
    public function __construct(
        private readonly string $id,
        #[SensitiveParameter]
        private readonly string $token,
        private readonly array $attributes,
    ) {}

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
