<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;

#[OA\Info(
    version: '1.0.0',
    title: 'ADP Laravel Playground API',
    description: 'Demo API for the ADP Laravel Playground application.',
)]
final class HomeController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Translator $translator,
    ) {}

    #[OA\Get(
        path: '/api',
        summary: 'API index',
        tags: ['General'],
        responses: [
            new OA\Response(response: 200, description: 'API information', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'debug_panel', type: 'string'),
                new OA\Property(property: 'endpoints', type: 'object'),
            ])),
        ],
    )]
    public function index(): JsonResponse
    {
        $this->logger->info('Home page accessed');

        // Demo translations — makes TranslatorCollector visible in the panel
        $this->translator->get('messages.welcome', [], 'en');
        $this->translator->get('messages.welcome', [], 'de');
        $this->translator->get('messages.goodbye', [], 'fr'); // missing

        return new JsonResponse([
            'message' => 'Welcome to the ADP Laravel Playground API!',
            'debug_panel' => '/debug/',
            'endpoints' => [
                'GET /api' => 'This page',
                'GET /api/users' => 'List users (demo)',
                'GET /api/error' => 'Trigger an exception (demo)',
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/users',
        summary: 'List users',
        tags: ['Users'],
        responses: [
            new OA\Response(response: 200, description: 'List of users', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'users', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                ])),
            ])),
        ],
    )]
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

    #[OA\Get(
        path: '/api/error',
        summary: 'Trigger a demo exception',
        tags: ['General'],
        responses: [
            new OA\Response(response: 500, description: 'Demo exception'),
        ],
    )]
    public function error(): never
    {
        $this->logger->warning('About to trigger a demo exception');

        throw new \RuntimeException('This is a demo exception for ADP debugging');
    }
}
