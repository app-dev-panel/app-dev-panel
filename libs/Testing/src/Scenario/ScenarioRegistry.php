<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Scenario;

/**
 * Central registry of all ADP test scenarios.
 *
 * Scenarios are organized by collector feature. Each scenario defines:
 * - An endpoint to call (relative to the playground base URL)
 * - Expected collector data after the request completes
 *
 * All playgrounds must implement the `/test/scenarios/*` endpoints.
 * The endpoint contract is defined here; each adapter wires it to its framework.
 */
final class ScenarioRegistry
{
    /**
     * @return list<Scenario>
     */
    public static function all(): array
    {
        return [
            ...self::coreScenarios(),
            ...self::webScenarios(),
            ...self::errorScenarios(),
            ...self::advancedScenarios(),
        ];
    }

    /**
     * Get scenarios by tag (for selective runs).
     *
     * @return list<Scenario>
     */
    public static function byTag(string $tag): array
    {
        return match ($tag) {
            'core' => self::coreScenarios(),
            'web' => self::webScenarios(),
            'error' => self::errorScenarios(),
            'advanced' => self::advancedScenarios(),
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
     * Core collector scenarios — must work in every adapter.
     *
     * @return list<Scenario>
     */
    private static function coreScenarios(): array
    {
        return [
            // === Logging ===
            new Scenario(
                name: 'logs:basic',
                endpoint: '/test/scenarios/logs',
                expectations: [
                    'logger' => [
                        Expectation::notEmpty(),
                        Expectation::countGte(3),
                        Expectation::fieldEquals('0.level', 'info'),
                        Expectation::fieldContains('0.message', 'Test log: info'),
                        Expectation::fieldEquals('1.level', 'warning'),
                        Expectation::fieldEquals('2.level', 'error'),
                    ],
                ],
            ),

            // === Logging with context ===
            new Scenario(
                name: 'logs:context',
                endpoint: '/test/scenarios/logs-context',
                expectations: [
                    'logger' => [
                        Expectation::notEmpty(),
                        Expectation::countGte(1),
                        Expectation::fieldEquals('0.level', 'info'),
                        Expectation::fieldContains('0.message', 'User action'),
                    ],
                ],
            ),

            // === Events ===
            new Scenario(
                name: 'events:basic',
                endpoint: '/test/scenarios/events',
                expectations: [
                    'event' => [
                        Expectation::notEmpty(),
                        Expectation::countGte(1),
                    ],
                ],
            ),

            // === VarDumper ===
            new Scenario(
                name: 'var-dumper:basic',
                endpoint: '/test/scenarios/dump',
                expectations: [
                    'var-dumper' => [
                        Expectation::notEmpty(),
                        Expectation::countGte(1),
                    ],
                ],
            ),

            // === Timeline ===
            new Scenario(
                name: 'timeline:basic',
                endpoint: '/test/scenarios/timeline',
                expectations: [
                    'timeline' => [
                        Expectation::notEmpty(),
                        Expectation::countGte(1),
                    ],
                ],
            ),
        ];
    }

    /**
     * Web context scenarios — request/response and app info.
     *
     * @return list<Scenario>
     */
    private static function webScenarios(): array
    {
        return [
            // === Request collector ===
            new Scenario(
                name: 'request:basic',
                endpoint: '/test/scenarios/request-info',
                expectations: [
                    'request' => [
                        Expectation::notEmpty(),
                    ],
                ],
            ),

            // === Web app info (timing, memory) ===
            new Scenario(
                name: 'web:app-info',
                endpoint: '/test/scenarios/request-info',
                expectations: [
                    'web' => [
                        Expectation::notEmpty(),
                    ],
                ],
            ),
        ];
    }

    /**
     * Error/exception scenarios.
     *
     * @return list<Scenario>
     */
    private static function errorScenarios(): array
    {
        return [
            // === Exception collector ===
            new Scenario(
                name: 'exception:runtime',
                endpoint: '/test/scenarios/exception',
                expectations: [
                    'exception' => [
                        Expectation::notEmpty(),
                        Expectation::countGte(1),
                        Expectation::fieldEquals('0.class', 'RuntimeException'),
                        Expectation::fieldContains('0.message', 'ADP test scenario exception'),
                    ],
                ],
            ),

            // === Exception with previous ===
            new Scenario(
                name: 'exception:chained',
                endpoint: '/test/scenarios/exception-chained',
                expectations: [
                    'exception' => [
                        Expectation::notEmpty(),
                        Expectation::countGte(2),
                        Expectation::fieldContains('0.message', 'Wrapper exception'),
                        Expectation::fieldContains('1.message', 'Original cause'),
                    ],
                ],
            ),
        ];
    }

    /**
     * Advanced scenarios — multiple collectors interacting.
     *
     * @return list<Scenario>
     */
    private static function advancedScenarios(): array
    {
        return [
            // === Multiple collectors in one request ===
            new Scenario(
                name: 'multi:logs-and-events',
                endpoint: '/test/scenarios/multi',
                expectations: [
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
                ],
            ),

            // === Heavy logging — many entries ===
            new Scenario(
                name: 'logs:heavy',
                endpoint: '/test/scenarios/logs-heavy',
                expectations: [
                    'logger' => [
                        Expectation::notEmpty(),
                        Expectation::countGte(50),
                    ],
                ],
            ),

            // === HTTP client ===
            new Scenario(
                name: 'http-client:basic',
                endpoint: '/test/scenarios/http-client',
                expectations: [
                    'http' => [
                        Expectation::notEmpty(),
                        Expectation::countGte(1),
                    ],
                ],
            ),

            // === Filesystem stream ===
            new Scenario(
                name: 'filesystem:basic',
                endpoint: '/test/scenarios/filesystem',
                expectations: [
                    'fs_stream' => [
                        Expectation::notEmpty(),
                    ],
                ],
            ),
        ];
    }
}
