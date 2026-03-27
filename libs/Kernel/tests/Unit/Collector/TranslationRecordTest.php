<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\TranslationRecord;
use PHPUnit\Framework\TestCase;

final class TranslationRecordTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $record = new TranslationRecord(category: 'app', locale: 'en', message: 'hello');

        $this->assertSame('app', $record->category);
        $this->assertSame('en', $record->locale);
        $this->assertSame('hello', $record->message);
        $this->assertNull($record->translation);
        $this->assertFalse($record->missing);
        $this->assertNull($record->fallbackLocale);
    }

    public function testConstructorCustomValues(): void
    {
        $record = new TranslationRecord(
            category: 'messages',
            locale: 'de',
            message: 'welcome',
            translation: 'Willkommen!',
            missing: false,
            fallbackLocale: 'en',
        );

        $this->assertSame('messages', $record->category);
        $this->assertSame('de', $record->locale);
        $this->assertSame('welcome', $record->message);
        $this->assertSame('Willkommen!', $record->translation);
        $this->assertFalse($record->missing);
        $this->assertSame('en', $record->fallbackLocale);
    }

    public function testToArray(): void
    {
        $record = new TranslationRecord(
            category: 'app',
            locale: 'fr',
            message: 'greeting',
            translation: null,
            missing: true,
            fallbackLocale: 'en',
        );

        $array = $record->toArray();

        $this->assertSame('app', $array['category']);
        $this->assertSame('fr', $array['locale']);
        $this->assertSame('greeting', $array['message']);
        $this->assertNull($array['translation']);
        $this->assertTrue($array['missing']);
        $this->assertSame('en', $array['fallbackLocale']);
    }
}
