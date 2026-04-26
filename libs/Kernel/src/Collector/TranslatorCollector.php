<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Captures translation lookups during request execution.
 *
 * Framework adapters call logTranslation() with normalized data.
 * Tracks missing translations and per-locale/category usage.
 */
final class TranslatorCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var list<array{category: string, locale: string, message: string, translation: ?string, missing: bool, fallbackLocale: ?string}> */
    private array $translations = [];
    private int $missingCount = 0;

    public function logTranslation(TranslationRecord $record): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->translations[] = $record->toArray();

        if ($record->missing) {
            ++$this->missingCount;
        }
    }

    public function getCollected(): array
    {
        return [
            'translations' => $this->translations,
            'missingCount' => $this->missingCount,
            'totalCount' => count($this->translations),
            'locales' => $this->getUsedLocales(),
            'categories' => $this->getUsedCategories(),
        ];
    }

    public function getSummary(): array
    {
        return [
            'translator' => [
                'total' => count($this->translations),
                'missing' => $this->missingCount,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function getUsedLocales(): array
    {
        return array_values(array_unique(array_column($this->translations, 'locale')));
    }

    /**
     * @return list<string>
     */
    private function getUsedCategories(): array
    {
        return array_values(array_unique(array_column($this->translations, 'category')));
    }

    protected function reset(): void
    {
        $this->translations = [];
        $this->missingCount = 0;
    }
}
