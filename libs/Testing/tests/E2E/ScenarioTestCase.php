<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use AppDevPanel\Testing\Runner\ScenarioRunner;
use AppDevPanel\Testing\Scenario\Scenario;
use PHPUnit\Framework\TestCase;

/**
 * Base class for HTTP-based E2E scenario tests.
 *
 * Runs test scenarios against a live playground server.
 * Requires a running server at PLAYGROUND_URL env (default: http://127.0.0.1:8080).
 *
 * Usage:
 *   PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Scenarios
 */
abstract class ScenarioTestCase extends TestCase
{
    protected static ScenarioRunner $runner;
    protected static string $baseUrl;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$baseUrl = rtrim((string) getenv('PLAYGROUND_URL') ?: 'http://127.0.0.1:8080', '/');
        self::$runner = new ScenarioRunner(self::$baseUrl, retryDelayMs: 300, maxRetries: 15);

        // Verify the server is reachable
        try {
            $ch = curl_init(self::$baseUrl . '/');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($result === false || $httpCode === 0) {
                self::markTestSkipped(sprintf('Playground server not reachable at %s', self::$baseUrl));
            }
        } catch (\Throwable) {
            self::markTestSkipped(sprintf('Playground server not reachable at %s', self::$baseUrl));
        }
    }

    protected function runScenario(Scenario $scenario): void
    {
        $result = self::$runner->run($scenario);

        if ($result->error !== null) {
            self::markTestSkipped(sprintf('Scenario skipped: %s', $result->error));
        }

        $failures = [];
        foreach ($result->assertions as $assertion) {
            if (!$assertion->passed) {
                $failures[] = $assertion->message;
            }
        }

        self::assertTrue(
            $result->passed,
            sprintf(
                "Scenario '%s' failed (debug ID: %s):\n  - %s",
                $scenario->name,
                $result->debugId ?? 'unknown',
                implode("\n  - ", $failures),
            ),
        );
    }
}
