<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\JsonResponse;

final readonly class TranslatorAction
{
    public function __construct(
        private Translator $translator,
    ) {}

    public function __invoke(): JsonResponse
    {
        // Found: en → "Welcome!"
        $this->translator->get('messages.welcome', [], 'en');

        // Found: de → "Willkommen!"
        $this->translator->get('messages.welcome', [], 'de');

        // Found: en → "Goodbye!"
        $this->translator->get('messages.goodbye', [], 'en');

        // Missing: fr has no translations file
        $this->translator->get('messages.welcome', [], 'fr');

        return new JsonResponse(['fixture' => 'translator:basic', 'status' => 'ok']);
    }
}
