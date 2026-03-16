<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(LoggerInterface $logger): JsonResponse
    {
        $logger->info('Home page accessed');

        return $this->json([
            'message' => 'Welcome to the ADP Symfony Playground!',
            'debug_panel' => '/debug/api/',
            'endpoints' => [
                'GET /' => 'This page',
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
