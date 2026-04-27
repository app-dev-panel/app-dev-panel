<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Captures authentication and authorization data.
 *
 * Framework adapters call collection methods with normalized data extracted
 * from their security systems: user info, tokens, access decisions,
 * authentication events, guards, role hierarchy, and impersonation.
 */
final class AuthorizationCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array{username: ?string, roles: string[], effectiveRoles: string[], firewallName: ?string, authenticated: bool} */
    private array $identity = [
        'username' => null,
        'roles' => [],
        'effectiveRoles' => [],
        'firewallName' => null,
        'authenticated' => false,
    ];

    /** @var array{type: string, attributes: array<string, mixed>, expiresAt: string|null}|null */
    private ?array $token = null;

    /** @var array{originalUser: string, impersonatedUser: string}|null */
    private ?array $impersonation = null;

    /** @var array<int, array{name: string, provider: string, config: array<string, mixed>}> */
    private array $guards = [];

    /** @var array<string, string[]> */
    private array $roleHierarchy = [];

    /** @var array<int, array{type: string, provider: string, result: string, time: float, details: array<string, mixed>}> */
    private array $authenticationEvents = [];

    /** @var array<int, array{attribute: string, subject: string, result: string, voters: array, duration: float|null, context: array<string, mixed>}> */
    private array $accessDecisions = [];

    public function collectUser(?string $username, array $roles, bool $authenticated): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->identity['username'] = $username;
        $this->identity['roles'] = $roles;
        $this->identity['authenticated'] = $authenticated;
    }

    public function collectFirewall(string $firewallName): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->identity['firewallName'] = $firewallName;
    }

    /**
     * Collect token/session information (JWT, API key, session, OAuth, etc.).
     *
     * @param array<string, mixed> $attributes Token claims or metadata
     */
    public function collectToken(string $type, array $attributes = [], ?string $expiresAt = null): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->token = [
            'type' => $type,
            'attributes' => $attributes,
            'expiresAt' => $expiresAt,
        ];
    }

    /**
     * Collect impersonation (switch user) data.
     */
    public function collectImpersonation(string $originalUser, string $impersonatedUser): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->impersonation = [
            'originalUser' => $originalUser,
            'impersonatedUser' => $impersonatedUser,
        ];
    }

    /**
     * Collect guard/firewall configuration.
     *
     * @param array<string, mixed> $config Guard-specific configuration
     */
    public function collectGuard(string $name, string $provider, array $config = []): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->guards[] = [
            'name' => $name,
            'provider' => $provider,
            'config' => $config,
        ];
    }

    /**
     * Collect role hierarchy mapping (role → child roles).
     *
     * @param array<string, string[]> $hierarchy
     */
    public function collectRoleHierarchy(array $hierarchy): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->roleHierarchy = $hierarchy;
    }

    /**
     * Collect effective roles (resolved from hierarchy).
     *
     * @param string[] $effectiveRoles
     */
    public function collectEffectiveRoles(array $effectiveRoles): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->identity['effectiveRoles'] = $effectiveRoles;
    }

    /**
     * Log an authentication event (login, logout, failure, token refresh).
     *
     * @param string $type One of: login, logout, failure, token_refresh
     * @param array<string, mixed> $details Provider-specific details
     */
    public function collectAuthenticationEvent(
        string $type,
        string $provider,
        string $result,
        array $details = [],
    ): void {
        if (!$this->isActive()) {
            return;
        }

        $this->authenticationEvents[] = [
            'type' => $type,
            'provider' => $provider,
            'result' => $result,
            'time' => microtime(true),
            'details' => $details,
        ];
    }

    /**
     * Log an access decision (authorization check).
     *
     * @param array<string, mixed> $context Additional context (e.g., backtrace, resource info)
     */
    public function logAccessDecision(
        string $attribute,
        string $subject,
        string $result,
        array $voters = [],
        ?float $duration = null,
        array $context = [],
    ): void {
        if (!$this->isActive()) {
            return;
        }

        $this->accessDecisions[] = [
            'attribute' => $attribute,
            'subject' => $subject,
            'result' => $result,
            'voters' => $voters,
            'duration' => $duration,
            'context' => $context,
        ];
    }

    public function getCollected(): array
    {
        return [
            'username' => $this->identity['username'],
            'roles' => $this->identity['roles'],
            'effectiveRoles' => $this->identity['effectiveRoles'],
            'firewallName' => $this->identity['firewallName'],
            'authenticated' => $this->identity['authenticated'],
            'token' => $this->token,
            'impersonation' => $this->impersonation,
            'guards' => $this->guards,
            'roleHierarchy' => $this->roleHierarchy,
            'authenticationEvents' => $this->authenticationEvents,
            'accessDecisions' => $this->accessDecisions,
        ];
    }

    public function getSummary(): array
    {
        $granted = 0;
        $denied = 0;
        foreach ($this->accessDecisions as $decision) {
            if (str_contains($decision['result'], 'GRANTED')) {
                $granted++;
            } else {
                $denied++;
            }
        }

        return [
            'authorization' => [
                'username' => $this->identity['username'],
                'authenticated' => $this->identity['authenticated'],
                'roles' => $this->identity['roles'],
                'accessDecisions' => [
                    'total' => count($this->accessDecisions),
                    'granted' => $granted,
                    'denied' => $denied,
                ],
                'authEvents' => count($this->authenticationEvents),
            ],
        ];
    }

    protected function reset(): void
    {
        $this->identity = [
            'username' => null,
            'roles' => [],
            'effectiveRoles' => [],
            'firewallName' => null,
            'authenticated' => false,
        ];
        $this->token = null;
        $this->impersonation = null;
        $this->guards = [];
        $this->roleHierarchy = [];
        $this->authenticationEvents = [];
        $this->accessDecisions = [];
    }
}
