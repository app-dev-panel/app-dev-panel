<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Security;

/**
 * Default hostname resolver for {@see UrlPolicy}: system DNS (A + AAAA).
 * Tests inject a closure instead so they never touch the network.
 */
final class SystemDnsResolver
{
    /**
     * @return list<string> empty when nothing resolves
     */
    public static function resolve(string $host): array
    {
        $ipv4 = gethostbynamel($host) ?: [];

        try {
            $records = dns_get_record($host, DNS_AAAA) ?: [];
        } catch (\Throwable) {
            $records = [];
        }

        $ipv6 = array_filter(array_column($records, 'ipv6'), is_string(...));

        return [...array_values($ipv4), ...array_values($ipv6)];
    }
}
