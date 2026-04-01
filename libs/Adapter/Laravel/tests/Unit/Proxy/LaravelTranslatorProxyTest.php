<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Proxy;

use AppDevPanel\Adapter\Laravel\Proxy\LaravelTranslatorProxy;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use Illuminate\Contracts\Translation\Translator;
use PHPUnit\Framework\TestCase;

final class LaravelTranslatorProxyTest extends TestCase
{
    private TranslatorCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new TranslatorCollector();
        $this->collector->startup();
    }

    public function testGetCollectsFoundTranslation(): void
    {
        $inner = $this->createMock(Translator::class);
        $inner->method('get')->willReturn('Welcome!');
        $inner->method('getLocale')->willReturn('en');

        $proxy = new LaravelTranslatorProxy($inner, $this->collector);

        $result = $proxy->get('messages.welcome', [], 'en');

        $this->assertSame('Welcome!', $result);

        $collected = $this->collector->getCollected();
        $this->assertSame(1, $collected['totalCount']);
        $this->assertSame(0, $collected['missingCount']);
        $this->assertSame('messages', $collected['translations'][0]['category']);
        $this->assertSame('welcome', $collected['translations'][0]['message']);
        $this->assertSame('Welcome!', $collected['translations'][0]['translation']);
        $this->assertFalse($collected['translations'][0]['missing']);
    }

    public function testGetCollectsMissingTranslation(): void
    {
        $inner = $this->createMock(Translator::class);
        // Laravel returns the key when translation is missing
        $inner->method('get')->willReturn('messages.welcome');
        $inner->method('getLocale')->willReturn('en');

        $proxy = new LaravelTranslatorProxy($inner, $this->collector);

        $result = $proxy->get('messages.welcome', [], 'fr');

        $this->assertSame('messages.welcome', $result);

        $collected = $this->collector->getCollected();
        $this->assertSame(1, $collected['missingCount']);
        $this->assertTrue($collected['translations'][0]['missing']);
        $this->assertNull($collected['translations'][0]['translation']);
    }

    public function testGetParsesGroupAndKey(): void
    {
        $inner = $this->createMock(Translator::class);
        $inner->method('get')->willReturn('Validated');
        $inner->method('getLocale')->willReturn('en');

        $proxy = new LaravelTranslatorProxy($inner, $this->collector);

        $proxy->get('validation.required', [], 'en');

        $collected = $this->collector->getCollected();
        $this->assertSame('validation', $collected['translations'][0]['category']);
        $this->assertSame('required', $collected['translations'][0]['message']);
    }

    public function testGetWithoutGroupUsesMessagesCategory(): void
    {
        $inner = $this->createMock(Translator::class);
        // JSON translation keys have no dot-separated group
        $inner->method('get')->willReturn('Bienvenue');
        $inner->method('getLocale')->willReturn('fr');

        $proxy = new LaravelTranslatorProxy($inner, $this->collector);

        $proxy->get('Welcome', [], 'fr');

        $collected = $this->collector->getCollected();
        $this->assertSame('messages', $collected['translations'][0]['category']);
        $this->assertSame('Welcome', $collected['translations'][0]['message']);
    }

    public function testChoiceCollectsTranslation(): void
    {
        $inner = $this->createMock(Translator::class);
        $inner->method('choice')->willReturn('2 items');
        $inner->method('getLocale')->willReturn('en');

        $proxy = new LaravelTranslatorProxy($inner, $this->collector);

        $result = $proxy->choice('messages.items_count', 2, [], 'en');

        $this->assertSame('2 items', $result);

        $collected = $this->collector->getCollected();
        $this->assertSame(1, $collected['totalCount']);
        $this->assertSame('messages', $collected['translations'][0]['category']);
        $this->assertSame('items_count', $collected['translations'][0]['message']);
        $this->assertFalse($collected['translations'][0]['missing']);
    }

    public function testGetLocaleForwardsToDecorated(): void
    {
        $inner = $this->createMock(Translator::class);
        $inner->method('getLocale')->willReturn('de');

        $proxy = new LaravelTranslatorProxy($inner, $this->collector);

        $this->assertSame('de', $proxy->getLocale());
    }

    public function testSetLocaleForwardsToDecorated(): void
    {
        $inner = $this->createMock(Translator::class);
        $inner->expects($this->once())->method('setLocale')->with('de');

        $proxy = new LaravelTranslatorProxy($inner, $this->collector);

        $proxy->setLocale('de');
    }

    public function testUsesDefaultLocaleWhenNull(): void
    {
        $inner = $this->createMock(Translator::class);
        $inner->method('get')->willReturn('Welcome!');
        $inner->method('getLocale')->willReturn('ja');

        $proxy = new LaravelTranslatorProxy($inner, $this->collector);

        $proxy->get('messages.welcome');

        $collected = $this->collector->getCollected();
        $this->assertSame('ja', $collected['translations'][0]['locale']);
    }

    public function testChoiceWithMissingTranslation(): void
    {
        $inner = $this->createMock(Translator::class);
        // Laravel returns the key when translation is missing
        $inner->method('choice')->willReturn('messages.items_count');
        $inner->method('getLocale')->willReturn('en');

        $proxy = new LaravelTranslatorProxy($inner, $this->collector);

        $result = $proxy->choice('messages.items_count', 5, [], 'en');

        $this->assertSame('messages.items_count', $result);

        $collected = $this->collector->getCollected();
        $this->assertSame(1, $collected['missingCount']);
        $this->assertTrue($collected['translations'][0]['missing']);
        $this->assertNull($collected['translations'][0]['translation']);
    }

    public function testChoiceWithoutGroupUsesMessagesCategory(): void
    {
        $inner = $this->createMock(Translator::class);
        $inner->method('choice')->willReturn('2 articles');
        $inner->method('getLocale')->willReturn('fr');

        $proxy = new LaravelTranslatorProxy($inner, $this->collector);

        $proxy->choice('articles', 2, [], 'fr');

        $collected = $this->collector->getCollected();
        $this->assertSame('messages', $collected['translations'][0]['category']);
        $this->assertSame('articles', $collected['translations'][0]['message']);
    }

    public function testChoiceUsesDefaultLocaleWhenNull(): void
    {
        $inner = $this->createMock(Translator::class);
        $inner->method('choice')->willReturn('1 item');
        $inner->method('getLocale')->willReturn('de');

        $proxy = new LaravelTranslatorProxy($inner, $this->collector);

        $proxy->choice('cart.items', 1);

        $collected = $this->collector->getCollected();
        $this->assertSame('de', $collected['translations'][0]['locale']);
        $this->assertSame('cart', $collected['translations'][0]['category']);
        $this->assertSame('items', $collected['translations'][0]['message']);
    }

    public function testGetWithArrayResultIsMarkedAsMissing(): void
    {
        $inner = $this->createMock(Translator::class);
        // Laravel returns an array for group-level access like get('validation')
        $inner->method('get')->willReturn(['required' => 'This field is required']);
        $inner->method('getLocale')->willReturn('en');

        $proxy = new LaravelTranslatorProxy($inner, $this->collector);

        $result = $proxy->get('validation');

        $this->assertIsArray($result);

        $collected = $this->collector->getCollected();
        // Array result is not equal to the string key, so missing=false
        $this->assertFalse($collected['translations'][0]['missing']);
        // Non-string result should have null translation
        $this->assertNull($collected['translations'][0]['translation']);
    }

    public function testMultipleTranslationsAccumulate(): void
    {
        $inner = $this->createMock(Translator::class);
        $inner->method('get')
            ->willReturnMap([
                ['messages.hello', [], 'en', 'Hello'],
                ['messages.goodbye', [], 'en', 'Goodbye'],
                ['messages.missing_key', [], 'en', 'messages.missing_key'],
            ]);
        $inner->method('getLocale')->willReturn('en');

        $proxy = new LaravelTranslatorProxy($inner, $this->collector);

        $proxy->get('messages.hello', [], 'en');
        $proxy->get('messages.goodbye', [], 'en');
        $proxy->get('messages.missing_key', [], 'en');

        $collected = $this->collector->getCollected();
        $this->assertSame(3, $collected['totalCount']);
        $this->assertSame(1, $collected['missingCount']);
    }

    public function testGetWithNestedDotKey(): void
    {
        $inner = $this->createMock(Translator::class);
        $inner->method('get')->willReturn('The field is required');
        $inner->method('getLocale')->willReturn('en');

        $proxy = new LaravelTranslatorProxy($inner, $this->collector);

        $proxy->get('validation.custom.email.required', [], 'en');

        $collected = $this->collector->getCollected();
        // Only the first dot separates the category from the message
        $this->assertSame('validation', $collected['translations'][0]['category']);
        $this->assertSame('custom.email.required', $collected['translations'][0]['message']);
    }
}
