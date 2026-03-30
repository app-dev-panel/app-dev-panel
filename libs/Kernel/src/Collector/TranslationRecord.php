<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

final readonly class TranslationRecord
{
    public function __construct(
        public string $category,
        public string $locale,
        public string $message,
        public ?string $translation = null,
        public bool $missing = false,
        public ?string $fallbackLocale = null,
    ) {}

    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'locale' => $this->locale,
            'message' => $this->message,
            'translation' => $this->translation,
            'missing' => $this->missing,
            'fallbackLocale' => $this->fallbackLocale,
        ];
    }
}
