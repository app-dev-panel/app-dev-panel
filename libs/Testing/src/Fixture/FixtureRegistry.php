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
                    Expectation::fieldEquals('0.level', 'info'),
                    Expectation::fieldContains('0.message', 'Test log: info'),
                    Expectation::fieldEquals('1.level', 'warning'),
                    Expectation::fieldEquals('2.level', 'error'),
                ],
            ]),

            // === Logging with context ===
            new Fixture(name: 'logs:context', endpoint: '/test/fixtures/logs-context', expectations: [
                'logger' => [
                    Expectation::notEmpty(),
                    Expectation::countGte(1),
                    Expectation::fieldEquals('0.level', 'info'),
                    Expectation::fieldContains('0.message', 'User action'),
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
                    Expectation::fieldEquals('0.class', 'RuntimeException'),
                    Expectation::fieldContains('0.message', 'ADP test fixture exception'),
                ],
            ]),

            // === Exception with previous ===
            new Fixture(name: 'exception:chained', endpoint: '/test/fixtures/exception-chained', expectations: [
                'exception' => [
                    Expectation::notEmpty(),
                    Expectation::countGte(2),
                    Expectation::fieldContains('0.message', 'Wrapper exception'),
                    Expectation::fieldContains('1.message', 'Original cause'),
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

            // === Filesystem stream ===
            new Fixture(name: 'filesystem:basic', endpoint: '/test/fixtures/filesystem', expectations: [
                'fs_stream' => [
                    Expectation::notEmpty(),
                ],
            ]),
        ];
    }
}
