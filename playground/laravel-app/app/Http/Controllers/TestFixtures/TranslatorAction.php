<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use AppDevPanel\Kernel\Collector\TranslationRecord;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use Illuminate\Http\JsonResponse;

final readonly class TranslatorAction
{
    public function __construct(
        private TranslatorCollector $translatorCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->translatorCollector->logTranslation(new TranslationRecord(
            category: 'app',
            locale: 'en',
            message: 'welcome',
            translation: 'Welcome!',
        ));

        $this->translatorCollector->logTranslation(new TranslationRecord(
            category: 'app',
            locale: 'de',
            message: 'welcome',
            translation: 'Willkommen!',
        ));

        $this->translatorCollector->logTranslation(new TranslationRecord(
            category: 'app',
            locale: 'en',
            message: 'goodbye',
            translation: 'Goodbye!',
        ));

        $this->translatorCollector->logTranslation(new TranslationRecord(
            category: 'app',
            locale: 'fr',
            message: 'welcome',
            missing: true,
        ));

        return new JsonResponse(['fixture' => 'translator:basic', 'status' => 'ok']);
    }
}
