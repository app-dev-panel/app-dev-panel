<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PageController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(LoggerInterface $logger): Response
    {
        $logger->info('Home page accessed');
        return $this->render('page/home.html.twig');
    }

    #[Route('/users', name: 'users', methods: ['GET'])]
    public function users(LoggerInterface $logger): Response
    {
        $logger->info('Users page accessed');
        $users = [
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com', 'role' => 'Admin'],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com', 'role' => 'Editor'],
            ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com', 'role' => 'Viewer'],
        ];
        return $this->render('page/users.html.twig', ['users' => $users]);
    }

    #[Route('/contact', name: 'contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, LoggerInterface $logger): Response
    {
        $errors = [];
        $success = false;
        $data = [];

        if ($request->isMethod('POST')) {
            $data = [
                'name' => $request->request->get('name', ''),
                'email' => $request->request->get('email', ''),
                'message' => $request->request->get('message', ''),
            ];

            if (empty($data['name'])) {
                $errors['name'] = 'Name is required.';
            }
            if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'A valid email is required.';
            }
            if (empty($data['message'])) {
                $errors['message'] = 'Message is required.';
            }

            if (empty($errors)) {
                $logger->info('Contact form submitted', $data);
                $success = true;
                $data = [];
            }
        }

        return $this->render('page/contact.html.twig', [
            'errors' => $errors,
            'success' => $success,
            'data' => $data,
        ]);
    }

    #[Route('/api-playground', name: 'api_playground', methods: ['GET'])]
    public function apiPlayground(): Response
    {
        return $this->render('page/api-playground.html.twig');
    }

    #[Route('/error', name: 'error_demo', methods: ['GET'])]
    public function errorDemo(LoggerInterface $logger): Response
    {
        $logger->warning('About to trigger a demo exception');
        throw new \RuntimeException('This is a demo exception triggered from the Error Demo page');
    }
}
