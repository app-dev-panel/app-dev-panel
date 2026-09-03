<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Security;

use Closure;
use InvalidArgumentException;

/**
 * SSRF guard for operator-supplied URLs the panel will connect to
 * (external service `inspectorUrl` — see ServiceController / InspectorProxyMiddleware).
 *
 * Always enforced, in every mode:
 *  - scheme must be `http` or `https`;
 *  - no userinfo (`user:pass@`);
 *  - host required; a literal IP and every address the host resolves to must not be
 *    a forbidden address (link-local / cloud metadata 169.254.0.0/16 and fe80::/10,
 *    unspecified 0.0.0.0/8 and ::, multicast, reserved 240.0.0.0/4 —
 *    see {@see NetworkAddressClassifier});
 *  - unresolvable hosts are rejected (fail closed).
 *
 * By default loopback and private networks (127/8, ::1, `localhost`, RFC1918, CGNAT,
 * ULA) are **accepted** — inspected services normally run on localhost or in the same
 * docker network. `restrictToPublicHosts` turns on the strict mode that refuses them too.
 *
 * The resolver is injectable so unit tests never touch DNS.
 */
final class UrlPolicy
{
    private const array ALLOWED_SCHEMES = ['http', 'https'];

    /** @var Closure(string): list<string> */
    private readonly Closure $resolver;

    /**
     * @param bool $restrictToPublicHosts Strict mode: also refuse loopback / private-network targets.
     * @param null|callable(string): list<string> $resolver Hostname -> IP list; defaults to system DNS.
     */
    public function __construct(
        private readonly bool $restrictToPublicHosts = false,
        ?callable $resolver = null,
    ) {
        $this->resolver = $resolver === null ? SystemDnsResolver::resolve(...) : Closure::fromCallable($resolver);
    }

    public function restrictsToPublicHosts(): bool
    {
        return $this->restrictToPublicHosts;
    }

    public function isAllowed(string $url): bool
    {
        try {
            $this->assertAllowed($url);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }

    /**
     * @throws InvalidArgumentException with a human-readable reason
     */
    public function assertAllowed(string $url): void
    {
        $parts = $this->parse($url);

        $this->assertScheme((string) $parts['scheme']);
        $this->assertNoUserInfo($parts);
        $this->assertHost(strtolower(trim((string) $parts['host'], '[]')));
    }

    /**
     * @return array<string, int|string>
     */
    private function parse(string $url): array
    {
        $parts = parse_url($url) ?: [];
        if (!array_key_exists('scheme', $parts) || !array_key_exists('host', $parts)) {
            throw new InvalidArgumentException(sprintf('URL "%s" is malformed or has no host.', $url));
        }

        return $parts;
    }

    private function assertScheme(string $scheme): void
    {
        if (!in_array(strtolower($scheme), self::ALLOWED_SCHEMES, true)) {
            throw new InvalidArgumentException(sprintf('URL scheme "%s" is not allowed; use http or https.', $scheme));
        }
    }

    /**
     * @param array<string, int|string> $parts
     */
    private function assertNoUserInfo(array $parts): void
    {
        if (array_key_exists('user', $parts) || array_key_exists('pass', $parts)) {
            throw new InvalidArgumentException('URL must not contain credentials (userinfo).');
        }
    }

    private function assertHost(string $host): void
    {
        // `localhost` / `*.localhost` is loopback by definition — judged as 127.0.0.1 without DNS.
        if (NetworkAddressClassifier::isLoopbackHostname($host)) {
            $this->assertAddress($host, '127.0.0.1');

            return;
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) !== false ? [$host] : ($this->resolver)($host);

        if ($addresses === []) {
            throw new InvalidArgumentException(sprintf('Host "%s" could not be resolved.', $host));
        }

        foreach ($addresses as $address) {
            $this->assertAddress($host, $address);
        }
    }

    private function assertAddress(string $host, string $address): void
    {
        if (NetworkAddressClassifier::isForbiddenAddress($address)) {
            throw new InvalidArgumentException(sprintf(
                'Host "%s" resolves to a link-local, unspecified, multicast or reserved address (%s).',
                $host,
                $address,
            ));
        }

        if ($this->restrictToPublicHosts && NetworkAddressClassifier::isPrivateAddress($address)) {
            throw new InvalidArgumentException(sprintf(
                'Host "%s" resolves to a private or loopback address (%s); only public hosts are allowed.',
                $host,
                $address,
            ));
        }
    }
}
