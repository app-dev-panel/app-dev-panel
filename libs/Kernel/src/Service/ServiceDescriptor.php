<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Service;

final readonly class ServiceDescriptor
{
    /**
     * @param string $service unique service identifier
     * @param string $language programming language (e.g. "python", "typescript", "php")
     * @param string|null $inspectorUrl base URL for inspector proxy (null for local PHP)
     * @param string[] $capabilities supported inspector capabilities
     * @param float $registeredAt microtime when service was registered
     * @param float $lastSeenAt microtime of last heartbeat
     */
    public function __construct(
        public string $service,
        public string $language,
        public ?string $inspectorUrl,
        public array $capabilities,
        public float $registeredAt,
        public float $lastSeenAt,
    ) {}

    public function supports(string $capability): bool
    {
        return in_array($capability, $this->capabilities, true) || in_array('*', $this->capabilities, true);
    }

    public function isOnline(float $timeoutSeconds = 60.0): bool
    {
        return (microtime(true) - $this->lastSeenAt) < $timeoutSeconds;
    }

    public function withLastSeen(float $time): self
    {
        return new self(
            $this->service,
            $this->language,
            $this->inspectorUrl,
            $this->capabilities,
            $this->registeredAt,
            $time,
        );
    }

    public function toArray(): array
    {
        return [
            'service' => $this->service,
            'language' => $this->language,
            'inspectorUrl' => $this->inspectorUrl,
            'capabilities' => $this->capabilities,
            'registeredAt' => $this->registeredAt,
            'lastSeenAt' => $this->lastSeenAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            service: (string) $data['service'],
            language: (string) ($data['language'] ?? 'unknown'),
            inspectorUrl: array_key_exists('inspectorUrl', $data) ? (string) $data['inspectorUrl'] : null,
            capabilities: (array) ($data['capabilities'] ?? []),
            registeredAt: (float) ($data['registeredAt'] ?? microtime(true)),
            lastSeenAt: (float) ($data['lastSeenAt'] ?? microtime(true)),
        );
    }
}
