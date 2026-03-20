<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Captures authentication and authorization data.
 *
 * Framework adapters call collectUser(), collectFirewall(), and logAccessDecision()
 * with normalized data extracted from their security systems.
 */
final class SecurityCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private ?string $username = null;
    private array $roles = [];
    private ?string $firewallName = null;
    private bool $authenticated = false;
    /** @var array<int, array{attribute: string, subject: string, result: string, voters: array}> */
    private array $accessDecisions = [];

    public function collectUser(?string $username, array $roles, bool $authenticated): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->username = $username;
        $this->roles = $roles;
        $this->authenticated = $authenticated;
    }

    public function collectFirewall(string $firewallName): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->firewallName = $firewallName;
    }

    public function logAccessDecision(string $attribute, string $subject, string $result, array $voters = []): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->accessDecisions[] = [
            'attribute' => $attribute,
            'subject' => $subject,
            'result' => $result,
            'voters' => $voters,
        ];
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'username' => $this->username,
            'roles' => $this->roles,
            'firewallName' => $this->firewallName,
            'authenticated' => $this->authenticated,
            'accessDecisions' => $this->accessDecisions,
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'security' => [
                'username' => $this->username,
                'authenticated' => $this->authenticated,
                'roles' => $this->roles,
            ],
        ];
    }

    protected function reset(): void
    {
        $this->username = null;
        $this->roles = [];
        $this->firewallName = null;
        $this->authenticated = false;
        $this->accessDecisions = [];
    }
}
