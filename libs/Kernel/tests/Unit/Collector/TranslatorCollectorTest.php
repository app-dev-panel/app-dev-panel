<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\TranslationRecord;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class TranslatorCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new TranslatorCollector();
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        assert($collector instanceof TranslatorCollector, 'Expected TranslatorCollector instance');

        $collector->logTranslation(new TranslationRecord(
            category: 'app',
            locale: 'en',
            message: 'welcome',
            translation: 'Welcome!',
        ));
        $collector->logTranslation(new TranslationRecord(
            category: 'app',
            locale: 'de',
            message: 'welcome',
            translation: 'Willkommen!',
        ));
        $collector->logTranslation(new TranslationRecord(
            category: 'app',
            locale: 'en',
            message: 'goodbye',
            translation: 'Goodbye!',
        ));
        $collector->logTranslation(new TranslationRecord(
            category: 'app',
            locale: 'fr',
            message: 'welcome',
            missing: true,
        ));
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertArrayHasKey('translations', $data);
        $this->assertCount(4, $data['translations']);
        $this->assertSame(1, $data['missingCount']);
        $this->assertSame(4, $data['totalCount']);
        $this->assertEqualsCanonicalizing(['en', 'de', 'fr'], $data['locales']);
        $this->assertSame(['app'], $data['categories']);

        $this->assertSame('Welcome!', $data['translations'][0]['translation']);
        $this->assertFalse($data['translations'][0]['missing']);
        $this->assertTrue($data['translations'][3]['missing']);
        $this->assertNull($data['translations'][3]['translation']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('translator', $data);
        $this->assertSame(4, $data['translator']['total']);
        $this->assertSame(1, $data['translator']['missing']);
    }
}
