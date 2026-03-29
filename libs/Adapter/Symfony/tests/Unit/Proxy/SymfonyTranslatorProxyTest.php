<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Proxy;

use AppDevPanel\Adapter\Symfony\Proxy\SymfonyTranslatorProxy;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SymfonyTranslatorProxyTest extends TestCase
{
    private TranslatorCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new TranslatorCollector();
        $this->collector->startup();
    }

    public function testTransCollectsFoundTranslation(): void
    {
        $inner = $this->createMock(TranslatorInterface::class);
        $inner->method('trans')->willReturn('Welcome!');
        $inner->method('getLocale')->willReturn('en');

        $proxy = new SymfonyTranslatorProxy($inner, $this->collector);

        $result = $proxy->trans('welcome', [], 'messages', 'en');

        $this->assertSame('Welcome!', $result);

        $collected = $this->collector->getCollected();
        $this->assertSame(1, $collected['totalCount']);
        $this->assertSame(0, $collected['missingCount']);
        $this->assertSame('messages', $collected['translations'][0]['category']);
        $this->assertSame('en', $collected['translations'][0]['locale']);
        $this->assertSame('welcome', $collected['translations'][0]['message']);
        $this->assertSame('Welcome!', $collected['translations'][0]['translation']);
        $this->assertFalse($collected['translations'][0]['missing']);
    }

    public function testTransCollectsMissingTranslation(): void
    {
        $inner = $this->createMock(TranslatorInterface::class);
        // When translation is missing, Symfony returns the original ID
        $inner->method('trans')->willReturn('welcome');
        $inner->method('getLocale')->willReturn('en');

        $proxy = new SymfonyTranslatorProxy($inner, $this->collector);

        $result = $proxy->trans('welcome', [], 'messages', 'fr');

        $this->assertSame('welcome', $result);

        $collected = $this->collector->getCollected();
        $this->assertSame(1, $collected['totalCount']);
        $this->assertSame(1, $collected['missingCount']);
        $this->assertTrue($collected['translations'][0]['missing']);
        $this->assertNull($collected['translations'][0]['translation']);
    }

    public function testTransUsesDefaultDomainWhenNull(): void
    {
        $inner = $this->createMock(TranslatorInterface::class);
        $inner->method('trans')->willReturn('Hello');
        $inner->method('getLocale')->willReturn('en');

        $proxy = new SymfonyTranslatorProxy($inner, $this->collector);

        $proxy->trans('hello');

        $collected = $this->collector->getCollected();
        $this->assertSame('messages', $collected['translations'][0]['category']);
    }

    public function testTransUsesDefaultLocaleWhenNull(): void
    {
        $inner = $this->createMock(TranslatorInterface::class);
        $inner->method('trans')->willReturn('Hello');
        $inner->method('getLocale')->willReturn('de');

        $proxy = new SymfonyTranslatorProxy($inner, $this->collector);

        $proxy->trans('hello');

        $collected = $this->collector->getCollected();
        $this->assertSame('de', $collected['translations'][0]['locale']);
    }

    public function testGetLocaleForwardsToDecorated(): void
    {
        $inner = $this->createMock(TranslatorInterface::class);
        $inner->method('getLocale')->willReturn('fr');

        $proxy = new SymfonyTranslatorProxy($inner, $this->collector);

        $this->assertSame('fr', $proxy->getLocale());
    }

    public function testMultipleTranslationsCollected(): void
    {
        $inner = $this->createMock(TranslatorInterface::class);
        $inner
            ->method('trans')
            ->willReturnMap([
                ['welcome', [], 'messages', 'en', 'Welcome!'],
                ['welcome', [], 'messages', 'de', 'Willkommen!'],
                ['missing_key', [], 'messages', 'en', 'missing_key'],
            ]);
        $inner->method('getLocale')->willReturn('en');

        $proxy = new SymfonyTranslatorProxy($inner, $this->collector);

        $proxy->trans('welcome', [], 'messages', 'en');
        $proxy->trans('welcome', [], 'messages', 'de');
        $proxy->trans('missing_key', [], 'messages', 'en');

        $collected = $this->collector->getCollected();
        $this->assertSame(3, $collected['totalCount']);
        $this->assertSame(1, $collected['missingCount']);
        $this->assertContains('en', $collected['locales']);
        $this->assertContains('de', $collected['locales']);
    }

    public function testSummaryReturnsCorrectCounts(): void
    {
        $inner = $this->createMock(TranslatorInterface::class);
        $inner->method('trans')->willReturnOnConsecutiveCalls('Welcome!', 'missing_key');
        $inner->method('getLocale')->willReturn('en');

        $proxy = new SymfonyTranslatorProxy($inner, $this->collector);

        $proxy->trans('welcome', [], 'messages', 'en');
        $proxy->trans('missing_key', [], 'messages', 'en');

        $summary = $this->collector->getSummary();
        $this->assertSame(2, $summary['translator']['total']);
        $this->assertSame(1, $summary['translator']['missing']);
    }
}
