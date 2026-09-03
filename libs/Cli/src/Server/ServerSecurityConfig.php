<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Server;

/**
 * Resolves the network security settings of the standalone `adp serve` server.
 *
 * The API exposed by `server-router.php` can read files, run commands, install
 * Composer packages and execute raw SQL, so it must never be reachable by
 * anyone who merely finds the port. Defaults are therefore deny-by-default:
 *
 * - `ADP_ALLOWED_IPS` (comma separated) restricts clients; unset means
 *   loopback only (`127.0.0.1`, `::1`). The literal `*` allows every client.
 * - `ADP_AUTH_TOKEN` is required (`X-Debug-Token` header) whenever the server
 *   binds to a non-loopback host (see {@see unsafeBindReason()}), since the IP
 *   filter alone is not a sufficient guard on a shared network.
 */
final class ServerSecurityConfig
{
    public const string ENV_ALLOWED_IPS = 'ADP_ALLOWED_IPS';

    // @mago-expect lint:no-literal-password
    public const string ENV_AUTH_TOKEN = 'ADP_AUTH_TOKEN';

    public const string ENV_BIND_HOST = 'ADP_BIND_HOST';

    public const string ALLOW_ALL = '*';

    /** @var string[] */
    public const array LOOPBACK_IPS = ['127.0.0.1', '::1'];

    /**
     * @param string[] $allowedIps Empty list means "allow every client".
     */
    public function __construct(
        public readonly array $allowedIps,
        #[\SensitiveParameter]
        public readonly string $authToken,
    ) {}

    /**
     * @param array<string, mixed> $env Environment variables (`getenv()` shape).
     */
    public static function fromEnvironment(array $env): self
    {
        return new self(
            self::parseAllowedIps(self::envString($env, self::ENV_ALLOWED_IPS)),
            self::envString($env, self::ENV_AUTH_TOKEN),
        );
    }

    /**
     * Why serving on `$bindHost` with this configuration would be unsafe, or
     * `null` when it is acceptable. Binding to a non-loopback host requires a
     * token, since the IP filter alone is not a sufficient guard on a shared
     * network.
     */
    public function unsafeBindReason(string $bindHost): ?string
    {
        if ($this->authToken !== '' || self::isLoopbackHost($bindHost)) {
            return null;
        }

        return sprintf(
            'Refusing to serve the ADP API on non-loopback host "%s" without authentication. '
            . 'Set %s to a secret token (sent as the X-Debug-Token header) or bind to 127.0.0.1.',
            $bindHost,
            self::ENV_AUTH_TOKEN,
        );
    }

    /**
     * @return string[]
     */
    public static function parseAllowedIps(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return self::LOOPBACK_IPS;
        }
        if ($raw === self::ALLOW_ALL) {
            return [];
        }

        $ips = [];
        foreach (explode(',', $raw) as $ip) {
            $ip = trim($ip);
            if ($ip !== '') {
                $ips[] = $ip;
            }
        }

        return $ips === [] ? self::LOOPBACK_IPS : array_values(array_unique($ips));
    }

    public static function isLoopbackHost(string $host): bool
    {
        $host = strtolower(trim($host, "[] \t"));
        if ($host === 'localhost' || $host === '::1') {
            return true;
        }

        return str_starts_with($host, '127.');
    }

    /**
     * Whether the IP filter should reject every client when `$allowedIps` is
     * empty. Only the explicit `*` wildcard opens the server to everyone.
     */
    public function isStrict(): bool
    {
        return $this->allowedIps !== [];
    }

    /**
     * Human readable allow-list for startup summaries.
     */
    public function describeAllowedIps(): string
    {
        return $this->allowedIps === [] ? '(everyone)' : implode(', ', $this->allowedIps);
    }

    /**
     * Human readable token state for startup summaries — never the token itself.
     */
    public function describeAuthToken(): string
    {
        return $this->authToken === '' ? '(none)' : '(set)';
    }

    /**
     * Environment variables that reproduce this configuration in a child
     * process (the `php -S` router script).
     *
     * @return array<string, string>
     */
    public function toEnvironment(string $bindHost): array
    {
        return [
            self::ENV_BIND_HOST => $bindHost,
            self::ENV_ALLOWED_IPS => $this->allowedIps === [] ? self::ALLOW_ALL : implode(',', $this->allowedIps),
            self::ENV_AUTH_TOKEN => $this->authToken,
        ];
    }

    /**
     * @param array<string, mixed> $env
     */
    private static function envString(array $env, string $key): string
    {
        $value = $env[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
