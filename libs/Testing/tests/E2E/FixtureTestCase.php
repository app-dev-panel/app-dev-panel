<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use AppDevPanel\Testing\Fixture\Fixture;
use AppDevPanel\Testing\Runner\FixtureRunner;
use PHPUnit\Framework\TestCase;

/**
 * Base class for HTTP-based E2E fixture tests.
 *
 * Runs test fixtures against a live playground server.
 * Requires a running server at PLAYGROUND_URL env (default: http://127.0.0.1:8080).
 *
 * Usage:
 *   PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Fixtures
 */
abstract class FixtureTestCase extends TestCase
{
    protected static FixtureRunner $runner;
    protected static string $baseUrl;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$baseUrl = rtrim((string) getenv('PLAYGROUND_URL') ?: 'http://127.0.0.1:8080', '/');
        self::$runner = new FixtureRunner(self::$baseUrl, retryDelayMs: 300, maxRetries: 15);

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
                self::fail(sprintf('Playground server is not running. Start it and re-run. URL: %s', self::$baseUrl));
            }
        } catch (\Throwable) {
            self::fail(sprintf('Playground server is not running. Start it and re-run. URL: %s', self::$baseUrl));
        }
    }

    protected function runFixture(Fixture $fixture): void
    {
        $result = self::$runner->run($fixture);

        if ($result->error !== null) {
            self::fail(sprintf("Fixture '%s' error: %s", $fixture->name, $result->error));
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
                "Fixture '%s' failed (debug ID: %s):\n  - %s",
                $fixture->name,
                $result->debugId ?? 'unknown',
                implode("\n  - ", $failures),
            ),
        );
    }
}
