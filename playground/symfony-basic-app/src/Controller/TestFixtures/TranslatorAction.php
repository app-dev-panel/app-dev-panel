<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/test/fixtures/translator', name: 'test_translator', methods: ['GET'])]
final readonly class TranslatorAction
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    public function __invoke(): JsonResponse
    {
        // Found: en → "Welcome!"
        $this->translator->trans('welcome', [], 'messages', 'en');

        // Found: de → "Willkommen!"
        $this->translator->trans('welcome', [], 'messages', 'de');

        // Found: en → "Goodbye!"
        $this->translator->trans('goodbye', [], 'messages', 'en');

        // Missing: fr has no translations file
        $this->translator->trans('welcome', [], 'messages', 'fr');

        return new JsonResponse(['fixture' => 'translator:basic', 'status' => 'ok']);
    }
}
