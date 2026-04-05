<?php

declare(strict_types=1);

namespace App\Controller;

use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[OA\Info(
    version: '1.0.0',
    title: 'ADP Symfony Playground API',
    description: 'Demo API for the ADP Symfony Playground application.',
)]
final class HomeController extends AbstractController
{
    #[Route('/api', name: 'api_index', methods: ['GET'])]
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
    public function apiIndex(LoggerInterface $logger, TranslatorInterface $translator): JsonResponse
    {
        $logger->info('API index accessed');

        $translator->trans('welcome', [], 'messages', 'en');
        $translator->trans('welcome', [], 'messages', 'de');
        $translator->trans('goodbye', [], 'messages', 'fr'); // missing

        return $this->json([
            'message' => 'Welcome to the ADP Symfony Playground API!',
            'debug_panel' => '/debug/',
            'endpoints' => [
                'GET /api' => 'This page',
                'GET /api/users' => 'List users (demo)',
                'GET /api/error' => 'Trigger an exception (demo)',
            ],
        ]);
    }

    #[Route('/api/users', name: 'api_users', methods: ['GET'])]
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
    public function users(LoggerInterface $logger): JsonResponse
    {
        $logger->info('Users API called');
        $logger->debug('Fetching users from database', ['limit' => 10]);

        $users = [
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
            ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com'],
        ];

        $logger->info('Users fetched', ['count' => count($users)]);

        return $this->json(['users' => $users]);
    }

    #[Route('/api/error', name: 'api_error', methods: ['GET'])]
    #[OA\Get(
        path: '/api/error',
        summary: 'Trigger a demo exception',
        tags: ['General'],
        responses: [
            new OA\Response(response: 500, description: 'Demo exception'),
        ],
    )]
    public function error(LoggerInterface $logger): Response
    {
        $logger->warning('About to trigger a demo exception');

        throw new \RuntimeException('This is a demo exception for ADP debugging');
    }
}
