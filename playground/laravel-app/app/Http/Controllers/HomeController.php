<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;

final class HomeController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Translator $translator,
    ) {}

    public function index(): JsonResponse
    {
        $this->logger->info('Home page accessed');

        // Demo translations — makes TranslatorCollector visible in the panel
        $this->translator->get('messages.welcome', [], 'en');
        $this->translator->get('messages.welcome', [], 'de');
        $this->translator->get('messages.goodbye', [], 'fr'); // missing

        return new JsonResponse([
            'message' => 'Welcome to the ADP Laravel Playground API!',
            'debug_panel' => '/debug/api/',
            'endpoints' => [
                'GET /api' => 'This page',
                'GET /api/users' => 'List users (demo)',
                'GET /api/error' => 'Trigger an exception (demo)',
            ],
        ]);
    }

    public function users(): JsonResponse
    {
        $this->logger->info('Users API called');
        $this->logger->debug('Fetching users from database', ['limit' => 10]);

        $users = [
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
            ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com'],
        ];

        $this->logger->info('Users fetched', ['count' => count($users)]);

        return new JsonResponse(['users' => $users]);
    }

    public function error(): never
    {
        $this->logger->warning('About to trigger a demo exception');

        throw new \RuntimeException('This is a demo exception for ADP debugging');
    }
}
