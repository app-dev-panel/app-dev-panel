<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\TranslationRecord;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/translator', name: 'test_translator', methods: ['GET'])]
final readonly class TranslatorAction
{
    public function __construct(
        private TranslatorCollector $translatorCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->translatorCollector->logTranslation(new TranslationRecord(
            category: 'messages',
            locale: 'en',
            message: 'welcome',
            translation: 'Welcome!',
        ));

        $this->translatorCollector->logTranslation(new TranslationRecord(
            category: 'messages',
            locale: 'de',
            message: 'welcome',
            translation: 'Willkommen!',
        ));

        $this->translatorCollector->logTranslation(new TranslationRecord(
            category: 'messages',
            locale: 'en',
            message: 'goodbye',
            translation: 'Goodbye!',
        ));

        $this->translatorCollector->logTranslation(new TranslationRecord(
            category: 'messages',
            locale: 'fr',
            message: 'welcome',
            missing: true,
        ));

        return new JsonResponse(['fixture' => 'translator:basic', 'status' => 'ok']);
    }
}
