<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Proxy;

use AppDevPanel\Adapter\Yii2\Proxy\RouterMatchRecorder;
use AppDevPanel\Adapter\Yii2\Proxy\UrlRuleProxy;
use PHPUnit\Framework\TestCase;
use yii\web\UrlManager;
use yii\web\UrlRuleInterface;

final class UrlRuleProxyTest extends TestCase
{
    public function testParseRequestDelegatesToInnerRule(): void
    {
        $recorder = new RouterMatchRecorder();
        $inner = $this->createMock(UrlRuleInterface::class);
        $inner->method('parseRequest')->willReturn(['site/index', ['id' => '1']]);

        $proxy = new UrlRuleProxy($inner, $recorder);
        $manager = $this->createMock(UrlManager::class);
        $request = $this->createMock(\yii\web\Request::class);

        $result = $proxy->parseRequest($manager, $request);

        $this->assertSame(['site/index', ['id' => '1']], $result);
    }

    public function testParseRequestRecordsMatchOnSuccess(): void
    {
        $recorder = new RouterMatchRecorder();
        $inner = $this->createMock(UrlRuleInterface::class);
        $inner->method('parseRequest')->willReturn(['site/view', ['id' => '5']]);

        $proxy = new UrlRuleProxy($inner, $recorder);
        $manager = $this->createMock(UrlManager::class);
        $request = $this->createMock(\yii\web\Request::class);

        $proxy->parseRequest($manager, $request);

        $this->assertSame($inner, $recorder->getMatchedRule());
        $this->assertSame(['site/view', ['id' => '5']], $recorder->getMatchResult());
        $this->assertGreaterThanOrEqual(0, $recorder->getMatchTime());
    }

    public function testParseRequestDoesNotRecordOnFailure(): void
    {
        $recorder = new RouterMatchRecorder();
        $inner = $this->createMock(UrlRuleInterface::class);
        $inner->method('parseRequest')->willReturn(false);

        $proxy = new UrlRuleProxy($inner, $recorder);
        $manager = $this->createMock(UrlManager::class);
        $request = $this->createMock(\yii\web\Request::class);

        $result = $proxy->parseRequest($manager, $request);

        $this->assertFalse($result);
        $this->assertNull($recorder->getMatchedRule());
    }

    public function testCreateUrlDelegatesToInnerRule(): void
    {
        $recorder = new RouterMatchRecorder();
        $inner = $this->createMock(UrlRuleInterface::class);
        $inner->method('createUrl')->willReturn('/site/view?id=5');

        $proxy = new UrlRuleProxy($inner, $recorder);
        $manager = $this->createMock(UrlManager::class);

        $result = $proxy->createUrl($manager, 'site/view', ['id' => '5']);

        $this->assertSame('/site/view?id=5', $result);
    }

    public function testGetInnerRuleReturnsOriginal(): void
    {
        $recorder = new RouterMatchRecorder();
        $inner = $this->createMock(UrlRuleInterface::class);

        $proxy = new UrlRuleProxy($inner, $recorder);

        $this->assertSame($inner, $proxy->getInnerRule());
    }

    public function testMultipleRulesOnlyFirstMatchRecorded(): void
    {
        $recorder = new RouterMatchRecorder();

        $rule1 = $this->createMock(UrlRuleInterface::class);
        $rule1->method('parseRequest')->willReturn(false);

        $rule2 = $this->createMock(UrlRuleInterface::class);
        $rule2->method('parseRequest')->willReturn(['api/users', []]);

        $proxy1 = new UrlRuleProxy($rule1, $recorder);
        $proxy2 = new UrlRuleProxy($rule2, $recorder);

        $manager = $this->createMock(UrlManager::class);
        $request = $this->createMock(\yii\web\Request::class);

        // Simulate UrlManager iterating rules
        $result1 = $proxy1->parseRequest($manager, $request);
        $this->assertFalse($result1);

        $result2 = $proxy2->parseRequest($manager, $request);
        $this->assertSame(['api/users', []], $result2);

        // rule2 should be recorded as the match
        $this->assertSame($rule2, $recorder->getMatchedRule());
        // Match time should be > 0 (measured from first markStart)
        $this->assertGreaterThanOrEqual(0, $recorder->getMatchTime());
    }
}
