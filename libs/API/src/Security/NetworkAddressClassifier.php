<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Security;

/**
 * IP / hostname classification used by {@see UrlPolicy}.
 *
 * Addresses fall into three buckets:
 *  - **forbidden** — never a legitimate inspector target, refused in every mode:
 *    link-local / cloud metadata (169.254.0.0/16, fe80::/10), unspecified / "this
 *    network" (0.0.0.0/8, ::/128), multicast (224.0.0.0/4, ff00::/8), reserved
 *    (240.0.0.0/4 incl. broadcast); IPv4-mapped IPv6 is unwrapped and judged as IPv4;
 *  - **private** — loopback (127.0.0.0/8, ::1), RFC1918 (10/8, 172.16/12, 192.168/16),
 *    CGNAT (100.64.0.0/10), ULA (fc00::/7) and deprecated site-local (fec0::/10):
 *    the normal localhost / docker-compose case, refused only in public-hosts-only mode;
 *  - **public** — everything else.
 */
final class NetworkAddressClassifier
{
    /** @var list<string> */
    private const array FORBIDDEN_RANGES = [
        '0.0.0.0/8',
        '169.254.0.0/16',
        '224.0.0.0/4',
        '240.0.0.0/4',
        '::/128',
        'fe80::/10',
        'ff00::/8',
    ];

    /** @var list<string> */
    private const array PRIVATE_RANGES = [
        '127.0.0.0/8',
        '10.0.0.0/8',
        '100.64.0.0/10',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '::1/128',
        'fc00::/7',
        'fec0::/10',
    ];

    /**
     * Never allowed, regardless of policy mode. Non-IP input is forbidden too.
     */
    public static function isForbiddenAddress(string $address): bool
    {
        $binary = self::toBinary($address);

        return $binary === null || self::matchesAny($binary, self::FORBIDDEN_RANGES);
    }

    /**
     * Loopback, RFC1918, CGNAT, ULA or site-local.
     */
    public static function isPrivateAddress(string $address): bool
    {
        $binary = self::toBinary($address);

        return $binary !== null && self::matchesAny($binary, self::PRIVATE_RANGES);
    }

    /**
     * Neither forbidden nor private.
     */
    public static function isPublicAddress(string $address): bool
    {
        return !self::isForbiddenAddress($address) && !self::isPrivateAddress($address);
    }

    /**
     * Names that are loopback by definition, without asking DNS.
     */
    public static function isLoopbackHostname(string $host): bool
    {
        return in_array($host, ['', 'localhost'], true) || str_ends_with($host, '.localhost');
    }

    /**
     * Packed address; IPv4-mapped IPv6 (`::ffff:a.b.c.d`) collapses to its IPv4 payload
     * so a mapped loopback/metadata address cannot slip past the IPv4 ranges.
     */
    private static function toBinary(string $address): ?string
    {
        $binary = filter_var($address, FILTER_VALIDATE_IP) === false ? false : inet_pton($address);
        if ($binary === false) {
            return null;
        }

        $isMapped = strlen($binary) === 16 && str_starts_with($binary, "\0\0\0\0\0\0\0\0\0\0\xff\xff");

        return $isMapped ? substr($binary, 12) : $binary;
    }

    /**
     * @param list<string> $ranges CIDR notation
     */
    private static function matchesAny(string $binary, array $ranges): bool
    {
        foreach ($ranges as $range) {
            [$network, $bits] = explode('/', $range, 2);
            $networkBinary = (string) inet_pton($network);

            if (
                strlen($networkBinary) === strlen($binary)
                && self::matchesPrefix($binary, $networkBinary, (int) $bits)
            ) {
                return true;
            }
        }

        return false;
    }

    private static function matchesPrefix(string $binary, string $network, int $bits): bool
    {
        $fullBytes = intdiv($bits, 8);
        if (substr($binary, 0, $fullBytes) !== substr($network, 0, $fullBytes)) {
            return false;
        }

        $remainder = $bits % 8;
        if ($remainder === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remainder)) & 0xff;

        return (ord($binary[$fullBytes]) & $mask) === (ord($network[$fullBytes]) & $mask);
    }
}
