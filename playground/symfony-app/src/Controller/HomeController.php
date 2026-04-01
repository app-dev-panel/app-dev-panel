<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class HomeController extends AbstractController
{
    #[Route('/api', name: 'api_index', methods: ['GET'])]
    public function apiIndex(LoggerInterface $logger, TranslatorInterface $translator): JsonResponse
    {
        $logger->info('API index accessed');

        $translator->trans('welcome', [], 'messages', 'en');
        $translator->trans('welcome', [], 'messages', 'de');
        $translator->trans('goodbye', [], 'messages', 'fr'); // missing

        return $this->json([
            'message' => 'Welcome to the ADP Symfony Playground API!',
            'debug_panel' => '/debug/api/',
            'endpoints' => [
                'GET /api' => 'This page',
                'GET /api/users' => 'List users (demo)',
                'GET /api/error' => 'Trigger an exception (demo)',
            ],
        ]);
    }

    #[Route('/api/users', name: 'api_users', methods: ['GET'])]
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
    public function error(LoggerInterface $logger): Response
    {
        $logger->warning('About to trigger a demo exception');

        throw new \RuntimeException('This is a demo exception for ADP debugging');
    }
}
