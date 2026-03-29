<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Fixture;

/**
 * Central registry of all ADP test fixtures.
 *
 * Fixtures are organized by collector feature. Each fixture defines:
 * - An endpoint to call (relative to the playground base URL)
 * - Expected collector data after the request completes
 *
 * All playgrounds must implement the `/test/fixtures/*` endpoints.
 * The endpoint contract is defined here; each adapter wires it to its framework.
 */
final class FixtureRegistry
{
    /**
     * @return list<Fixture>
     */
    public static function all(): array
    {
        return [
            ...self::coreFixtures(),
            ...self::webFixtures(),
            ...self::errorFixtures(),
            ...self::advancedFixtures(),
        ];
    }

    /**
     * Get fixtures by tag (for selective runs).
     *
     * @return list<Fixture>
     */
    public static function byTag(string $tag): array
    {
        return match ($tag) {
            'core' => self::coreFixtures(),
            'web' => self::webFixtures(),
            'error' => self::errorFixtures(),
            'advanced' => self::advancedFixtures(),
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function tags(): array
    {
        return ['core', 'web', 'error', 'advanced'];
    }

    /**
     * Core collector fixtures — must work in every adapter.
     *
     * @return list<Fixture>
     */
    private static function coreFixtures(): array
    {
        return [
            // === Logging ===
            new Fixture(name: 'logs:basic', endpoint: '/test/fixtures/logs', expectations: [
                'logger' => [
                    Expectation::notEmpty(),
                    Expectation::countGte(3),
                    Expectation::anyFieldContains('message', 'Test log: info'),
                    Expectation::anyFieldEquals('level', 'info'),
                    Expectation::anyFieldEquals('level', 'warning'),
                    Expectation::anyFieldEquals('level', 'error'),
                ],
            ]),

            // === Logging with context ===
            new Fixture(name: 'logs:context', endpoint: '/test/fixtures/logs-context', expectations: [
                'logger' => [
                    Expectation::notEmpty(),
                    Expectation::countGte(1),
                    Expectation::anyFieldContains('message', 'User action'),
                ],
            ]),

            // === Events ===
            new Fixture(name: 'events:basic', endpoint: '/test/fixtures/events', expectations: [
                'event' => [
                    Expectation::notEmpty(),
                    Expectation::countGte(1),
                ],
            ]),

            // === VarDumper ===
            new Fixture(name: 'var-dumper:basic', endpoint: '/test/fixtures/dump', expectations: [
                'var-dumper' => [
                    Expectation::notEmpty(),
                    Expectation::countGte(1),
                ],
            ]),

            // === Timeline ===
            new Fixture(name: 'timeline:basic', endpoint: '/test/fixtures/timeline', expectations: [
                'timeline' => [
                    Expectation::notEmpty(),
                    Expectation::countGte(1),
                ],
            ]),
        ];
    }

    /**
     * Web context fixtures — request/response and app info.
     *
     * @return list<Fixture>
     */
    private static function webFixtures(): array
    {
        return [
            // === Request collector ===
            new Fixture(name: 'request:basic', endpoint: '/test/fixtures/request-info', expectations: [
                'request' => [
                    Expectation::notEmpty(),
                ],
            ]),

            // === Web app info (timing, memory) ===
            new Fixture(name: 'web:app-info', endpoint: '/test/fixtures/request-info', expectations: [
                'web' => [
                    Expectation::notEmpty(),
                ],
            ]),
        ];
    }

    /**
     * Error/exception fixtures.
     *
     * @return list<Fixture>
     */
    private static function errorFixtures(): array
    {
        return [
            // === Exception collector ===
            new Fixture(name: 'exception:runtime', endpoint: '/test/fixtures/exception', expectations: [
                'exception' => [
                    Expectation::notEmpty(),
                    Expectation::countGte(1),
                    Expectation::anyFieldEquals('class', 'RuntimeException'),
                    Expectation::anyFieldContains('message', 'ADP test fixture exception'),
                ],
            ]),

            // === Exception with previous ===
            new Fixture(name: 'exception:chained', endpoint: '/test/fixtures/exception-chained', expectations: [
                'exception' => [
                    Expectation::notEmpty(),
                    Expectation::countGte(2),
                    Expectation::anyFieldContains('message', 'Wrapper exception'),
                    Expectation::anyFieldContains('message', 'Original cause'),
                ],
            ]),
        ];
    }

    /**
     * Advanced fixtures — multiple collectors interacting.
     *
     * @return list<Fixture>
     */
    private static function advancedFixtures(): array
    {
        return [
            // === Multiple collectors in one request ===
            new Fixture(name: 'multi:logs-and-events', endpoint: '/test/fixtures/multi', expectations: [
                'logger' => [
                    Expectation::notEmpty(),
                    Expectation::countGte(2),
                ],
                'event' => [
                    Expectation::notEmpty(),
                    Expectation::countGte(1),
                ],
                'timeline' => [
                    Expectation::notEmpty(),
                ],
            ]),

            // === Heavy logging — many entries ===
            new Fixture(name: 'logs:heavy', endpoint: '/test/fixtures/logs-heavy', expectations: [
                'logger' => [
                    Expectation::notEmpty(),
                    Expectation::countGte(50),
                ],
            ]),

            // === HTTP client (GET, POST JSON, PUT, DELETE, OPTIONS, POST multipart) ===
            new Fixture(name: 'http-client:basic', endpoint: '/test/fixtures/http-client', expectations: [
                'http' => [
                    Expectation::notEmpty(),
                    Expectation::countGte(6),
                ],
            ]),

            // === Filesystem stream (file_put_contents, file_get_contents, unlink) ===
            new Fixture(name: 'filesystem:basic', endpoint: '/test/fixtures/filesystem', expectations: [
                'fs_stream' => [
                    Expectation::notEmpty(),
                    Expectation::countGte(2),
                ],
            ]),

            // === Filesystem stream — fopen/fwrite/fread + mkdir/rename/rmdir ===
            new Fixture(name: 'filesystem:streams', endpoint: '/test/fixtures/filesystem-streams', expectations: [
                'fs_stream' => [
                    Expectation::notEmpty(),
                    Expectation::countGte(4),
                ],
            ]),

            // === Database ===
            new Fixture(name: 'database:basic', endpoint: '/test/fixtures/database', expectations: [
                'db' => [
                    Expectation::notEmpty(),
                    Expectation::summaryHasKey('queries'),
                    Expectation::summaryGte('queries.total', 1),
                ],
            ]),

            // === Mailer ===
            new Fixture(name: 'mailer:basic', endpoint: '/test/fixtures/mailer', expectations: [
                'mailer' => [
                    Expectation::notEmpty(),
                    Expectation::summaryGte('total', 1),
                ],
            ]),

            // === Messenger / Queue messages ===
            new Fixture(name: 'messenger:basic', endpoint: '/test/fixtures/messenger', expectations: [
                'queue' => [
                    Expectation::notEmpty(),
                    Expectation::summaryGte('messageCount', 2),
                    Expectation::summaryGte('failedCount', 1),
                ],
            ]),

            // === Validator ===
            new Fixture(name: 'validator:basic', endpoint: '/test/fixtures/validator', expectations: [
                'validator' => [
                    Expectation::notEmpty(),
                    Expectation::countGte(2),
                ],
            ]),

            // === Router ===
            new Fixture(name: 'router:basic', endpoint: '/test/fixtures/router', expectations: [
                'router' => [
                    Expectation::notEmpty(),
                    Expectation::fieldEquals('currentRoute.name', 'test_router'),
                    Expectation::fieldEquals('currentRoute.pattern', '/test/fixtures/router'),
                    Expectation::fieldContains('currentRoute.uri', '/test/fixtures/router'),
                    Expectation::countGte(2),
                ],
            ]),

            // === Router auto-collection (verifies RouterDataExtractor works on any request) ===
            new Fixture(name: 'router:auto', endpoint: '/test/fixtures/logs', expectations: [
                'router' => [
                    Expectation::notEmpty(),
                    Expectation::fieldContains('currentRoute.uri', '/test/fixtures/logs'),
                ],
            ]),

            // === Cache ===
            new Fixture(name: 'cache:basic', endpoint: '/test/fixtures/cache', expectations: [
                'cache' => [
                    Expectation::notEmpty(),
                    Expectation::summaryHasKey('cache'),
                    Expectation::summaryGte('cache.totalOperations', 3),
                    Expectation::summaryGte('cache.hits', 1),
                    Expectation::summaryGte('cache.misses', 1),
                ],
            ]),

            // === Cache heavy — many operations, multiple pools ===
            new Fixture(name: 'cache:heavy', endpoint: '/test/fixtures/cache-heavy', expectations: [
                'cache' => [
                    Expectation::notEmpty(),
                    Expectation::summaryGte('cache.totalOperations', 100),
                    Expectation::summaryGte('cache.hits', 1),
                ],
            ]),

            // === Security ===
            new Fixture(name: 'security:basic', endpoint: '/test/fixtures/security', expectations: [
                'security' => [
                    Expectation::notEmpty(),
                    Expectation::fieldEquals('username', 'admin@example.com'),
                    Expectation::fieldEquals('authenticated', true),
                    Expectation::fieldEquals('firewallName', 'main'),
                    Expectation::summaryHasKey('security'),
                    Expectation::summaryGte('security.accessDecisions.total', 2),
                    Expectation::summaryGte('security.accessDecisions.granted', 1),
                    Expectation::summaryGte('security.accessDecisions.denied', 1),
                    Expectation::summaryGte('security.authEvents', 1),
                ],
            ]),

            // === OpenTelemetry ===
            new Fixture(name: 'opentelemetry:basic', endpoint: '/test/fixtures/opentelemetry', expectations: [
                'opentelemetry' => [
                    Expectation::notEmpty(),
                    Expectation::summaryHasKey('opentelemetry'),
                    Expectation::summaryGte('opentelemetry.spans', 4),
                    Expectation::summaryGte('opentelemetry.traces', 1),
                    Expectation::summaryGte('opentelemetry.errors', 1),
                ],
            ]),

            // === Translator ===
            new Fixture(name: 'translator:basic', endpoint: '/test/fixtures/translator', expectations: [
                'translator' => [
                    Expectation::notEmpty(),
                    Expectation::summaryHasKey('translator'),
                    Expectation::summaryGte('translator.total', 4),
                    Expectation::summaryGte('translator.missing', 1),
                ],
            ]),
        ];
    }
}
