<?php

declare(strict_types=1);

namespace AppDevPanel\Api;

final class ApiSecurityConfig
{
    /**
     * @param string[] $allowedIps
     * @param string[] $allowedHosts
     * @param string[] $requestReplayAllowedHosts
     * @param bool     $allowDestructiveOperations Controls whether the Inspector
     *                                             exposes endpoints that mutate
     *                                             the host system — running
     *                                             arbitrary commands, installing
     *                                             composer packages, deleting
     *                                             cache entries, executing raw
     *                                             SQL. Off by default: these are
     *                                             RCE-equivalent and should be
     *                                             enabled only after the
     *                                             operator has configured
     *                                             authentication.
     * @param bool     $restrictInspectorUrlsToPublicHosts
     *                                             Strict SSRF mode for external
     *                                             service `inspectorUrl`s (see
     *                                             {@see Security\UrlPolicy}): refuse
     *                                             loopback / RFC1918 / ULA targets as
     *                                             well. Off by default because
     *                                             inspected services normally run on
     *                                             localhost or in the same docker
     *                                             network. Link-local / cloud
     *                                             metadata, unspecified, multicast
     *                                             and reserved addresses, non-http(s)
     *                                             schemes and userinfo are refused in
     *                                             both modes.
     * @param bool     $exposeExceptionDetails     Include `file`, `line` and `trace`
     *                                             of uncaught exceptions in API 500
     *                                             responses (see
     *                                             {@see Debug\Middleware\ResponseDataWrapper}).
     *                                             On by default for local development.
     */
    // @mago-expect lint:excessive-parameter-list
    public function __construct(
        public readonly array $allowedIps = ['127.0.0.1', '::1'],
        #[\SensitiveParameter]
        public readonly string $authToken = '',
        public readonly array $allowedHosts = [],
        public readonly array $requestReplayAllowedHosts = ['127.0.0.1', 'localhost'],
        public readonly bool $allowDestructiveOperations = false,
        public readonly bool $restrictInspectorUrlsToPublicHosts = false,
        public readonly bool $exposeExceptionDetails = true,
    ) {}
}
