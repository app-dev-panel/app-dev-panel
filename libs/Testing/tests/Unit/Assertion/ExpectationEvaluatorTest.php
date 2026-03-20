<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\Unit\Assertion;

use AppDevPanel\Testing\Assertion\ExpectationEvaluator;
use AppDevPanel\Testing\Fixture\Expectation;
use PHPUnit\Framework\TestCase;

final class ExpectationEvaluatorTest extends TestCase
{
    public function testExistsPassesWhenCollectorPresent(): void
    {
        $evaluator = new ExpectationEvaluator();
        $results = $evaluator->evaluate('cache', ['some' => 'data'], [Expectation::exists()]);

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->passed);
    }

    public function testNotEmptyPassesWithData(): void
    {
        $evaluator = new ExpectationEvaluator();
        $results = $evaluator->evaluate('cache', ['operations' => []], [Expectation::notEmpty()]);

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->passed);
    }

    public function testNotEmptyFailsWithEmptyArray(): void
    {
        $evaluator = new ExpectationEvaluator();
        $results = $evaluator->evaluate('cache', [], [Expectation::notEmpty()]);

        $this->assertCount(1, $results);
        $this->assertFalse($results[0]->passed);
        $this->assertStringContainsString('empty', $results[0]->message);
    }

    public function testCountGtePassesWhenEnough(): void
    {
        $evaluator = new ExpectationEvaluator();
        $data = ['a', 'b', 'c'];
        $results = $evaluator->evaluate('cache', $data, [Expectation::countGte(3)]);

        $this->assertTrue($results[0]->passed);
    }

    public function testCountGteFailsWhenTooFew(): void
    {
        $evaluator = new ExpectationEvaluator();
        $results = $evaluator->evaluate('cache', ['a'], [Expectation::countGte(5)]);

        $this->assertFalse($results[0]->passed);
    }

    public function testFieldEqualsPassesOnMatch(): void
    {
        $evaluator = new ExpectationEvaluator();
        $data = ['pool' => 'default', 'operation' => 'get'];
        $results = $evaluator->evaluate('cache', $data, [Expectation::fieldEquals('pool', 'default')]);

        $this->assertTrue($results[0]->passed);
    }

    public function testFieldEqualsFailsOnMismatch(): void
    {
        $evaluator = new ExpectationEvaluator();
        $data = ['pool' => 'default'];
        $results = $evaluator->evaluate('cache', $data, [Expectation::fieldEquals('pool', 'redis')]);

        $this->assertFalse($results[0]->passed);
    }

    public function testFieldEqualsFailsOnMissingPath(): void
    {
        $evaluator = new ExpectationEvaluator();
        $results = $evaluator->evaluate('cache', [], [Expectation::fieldEquals('missing', 'value')]);

        $this->assertFalse($results[0]->passed);
    }

    public function testFieldContainsPassesOnSubstring(): void
    {
        $evaluator = new ExpectationEvaluator();
        $data = ['key' => 'user:42:profile'];
        $results = $evaluator->evaluate('cache', $data, [Expectation::fieldContains('key', 'user:42')]);

        $this->assertTrue($results[0]->passed);
    }

    public function testFieldContainsFailsOnNoMatch(): void
    {
        $evaluator = new ExpectationEvaluator();
        $data = ['key' => 'user:42'];
        $results = $evaluator->evaluate('cache', $data, [Expectation::fieldContains('key', 'session')]);

        $this->assertFalse($results[0]->passed);
    }

    public function testAnyFieldEqualsPassesWhenOneMatches(): void
    {
        $evaluator = new ExpectationEvaluator();
        $data = [
            ['operation' => 'set', 'key' => 'user:42'],
            ['operation' => 'get', 'key' => 'user:42'],
            ['operation' => 'get', 'key' => 'user:99'],
        ];
        $results = $evaluator->evaluate('cache', $data, [Expectation::anyFieldEquals('operation', 'set')]);

        $this->assertTrue($results[0]->passed);
    }

    public function testAnyFieldEqualsFailsWhenNoneMatch(): void
    {
        $evaluator = new ExpectationEvaluator();
        $data = [
            ['operation' => 'get', 'key' => 'user:42'],
            ['operation' => 'get', 'key' => 'user:99'],
        ];
        $results = $evaluator->evaluate('cache', $data, [Expectation::anyFieldEquals('operation', 'delete')]);

        $this->assertFalse($results[0]->passed);
    }

    public function testAnyFieldContainsPassesWhenOneMatches(): void
    {
        $evaluator = new ExpectationEvaluator();
        $data = [
            ['key' => 'user:42'],
            ['key' => 'session:abc'],
        ];
        $results = $evaluator->evaluate('cache', $data, [Expectation::anyFieldContains('key', 'session')]);

        $this->assertTrue($results[0]->passed);
    }

    public function testAnyFieldContainsFailsWhenNoneMatch(): void
    {
        $evaluator = new ExpectationEvaluator();
        $data = [
            ['key' => 'user:42'],
            ['key' => 'user:99'],
        ];
        $results = $evaluator->evaluate('cache', $data, [Expectation::anyFieldContains('key', 'session')]);

        $this->assertFalse($results[0]->passed);
    }

    public function testSummaryHasKeyIsDeferredPass(): void
    {
        $evaluator = new ExpectationEvaluator();
        $results = $evaluator->evaluate('cache', [], [Expectation::summaryHasKey('cache')]);

        $this->assertTrue($results[0]->passed);
        $this->assertStringContainsString('deferred', $results[0]->message);
    }

    public function testSummaryGteIsDeferredPass(): void
    {
        $evaluator = new ExpectationEvaluator();
        $results = $evaluator->evaluate('cache', [], [Expectation::summaryGte('cache.totalOperations', 3)]);

        $this->assertTrue($results[0]->passed);
        $this->assertStringContainsString('deferred', $results[0]->message);
    }

    public function testUnknownTypeFailsGracefully(): void
    {
        $evaluator = new ExpectationEvaluator();
        $results = $evaluator->evaluate('cache', [], [new Expectation('unknown_type')]);

        $this->assertFalse($results[0]->passed);
        $this->assertStringContainsString('Unknown assertion type', $results[0]->message);
    }

    public function testMultipleExpectationsEvaluatedInOrder(): void
    {
        $evaluator = new ExpectationEvaluator();
        $data = [
            ['operation' => 'set', 'key' => 'user:42', 'hit' => false],
            ['operation' => 'get', 'key' => 'user:42', 'hit' => true],
            ['operation' => 'get', 'key' => 'user:99', 'hit' => false],
        ];

        $expectations = [
            Expectation::notEmpty(),
            Expectation::countGte(3),
            Expectation::anyFieldEquals('operation', 'set'),
            Expectation::anyFieldEquals('operation', 'get'),
        ];

        $results = $evaluator->evaluate('cache', $data, $expectations);

        $this->assertCount(4, $results);
        foreach ($results as $result) {
            $this->assertTrue($result->passed, $result->message);
        }
    }

    public function testNestedDotPathResolution(): void
    {
        $evaluator = new ExpectationEvaluator();
        $data = ['cache' => ['hits' => 5, 'misses' => 2]];
        $results = $evaluator->evaluate('cache', $data, [Expectation::fieldEquals('cache.hits', 5)]);

        $this->assertTrue($results[0]->passed);
    }

    public function testNumericIndexPathResolution(): void
    {
        $evaluator = new ExpectationEvaluator();
        $data = [['key' => 'user:42'], ['key' => 'user:99']];
        $results = $evaluator->evaluate('cache', $data, [Expectation::fieldEquals('1.key', 'user:99')]);

        $this->assertTrue($results[0]->passed);
    }

    public function testCacheFixtureExpectationsAgainstMockData(): void
    {
        $evaluator = new ExpectationEvaluator();
        $data = [
            'operations' => [
                ['pool' => 'default', 'operation' => 'set', 'key' => 'user:42', 'hit' => false, 'duration' => 0.001],
                ['pool' => 'default', 'operation' => 'get', 'key' => 'user:42', 'hit' => true, 'duration' => 0.0005],
                ['pool' => 'default', 'operation' => 'get', 'key' => 'user:99', 'hit' => false, 'duration' => 0.0003],
                [
                    'pool' => 'default',
                    'operation' => 'delete',
                    'key' => 'user:42',
                    'hit' => false,
                    'duration' => 0.0002,
                ],
            ],
            'hits' => 1,
            'misses' => 1,
            'totalOperations' => 4,
        ];

        $expectations = [
            Expectation::notEmpty(),
        ];

        $results = $evaluator->evaluate('cache', $data, $expectations);

        foreach ($results as $result) {
            $this->assertTrue($result->passed, $result->message);
        }
    }
}
