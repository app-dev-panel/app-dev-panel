<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Proxy;

use yii\web\UrlRuleInterface;

/**
 * Records route matching attempts during UrlManager::parseRequest().
 *
 * Populated by UrlRuleProxy wrappers, consumed by WebListener to feed RouterCollector
 * with accurate route metadata (pattern, name, match time, host) and the full
 * sequence of attempted rules in checking order (matched and unmatched).
 *
 * Timing starts automatically on the first parseRequest() call and ends when a match is found.
 */
final class RouterMatchRecorder
{
    private ?UrlRuleInterface $matchedRule = null;
    private ?array $matchResult = null;
    private float $startTime = 0;
    private float $matchTime = 0;
    private bool $started = false;

    /** @var list<array{rule: UrlRuleInterface, matched: bool}> */
    private array $attempts = [];

    /**
     * Called by UrlRuleProxy before delegating parseRequest().
     * Records start time only on the first invocation.
     */
    public function markStartIfNeeded(): void
    {
        if (!$this->started) {
            $this->startTime = hrtime(true);
            $this->started = true;
        }
    }

    /**
     * Called by UrlRuleProxy when a rule does not match.
     */
    public function recordMiss(UrlRuleInterface $rule): void
    {
        $this->attempts[] = ['rule' => $rule, 'matched' => false];
    }

    /**
     * Called by UrlRuleProxy when a rule matches.
     * Records the elapsed time from first parseRequest() to the match.
     */
    public function recordMatch(UrlRuleInterface $rule, array $result): void
    {
        $this->matchTime = (hrtime(true) - $this->startTime) / 1e6;
        $this->matchedRule = $rule;
        $this->matchResult = $result;
        $this->attempts[] = ['rule' => $rule, 'matched' => true];
    }

    public function getMatchedRule(): ?UrlRuleInterface
    {
        return $this->matchedRule;
    }

    public function getMatchResult(): ?array
    {
        return $this->matchResult;
    }

    public function getMatchTime(): float
    {
        return $this->matchTime;
    }

    /**
     * @return list<array{rule: UrlRuleInterface, matched: bool}>
     */
    public function getAttempts(): array
    {
        return $this->attempts;
    }

    public function reset(): void
    {
        $this->matchedRule = null;
        $this->matchResult = null;
        $this->startTime = 0;
        $this->matchTime = 0;
        $this->started = false;
        $this->attempts = [];
    }
}
