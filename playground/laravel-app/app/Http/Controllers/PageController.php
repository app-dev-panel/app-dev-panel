<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

final class PageController
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function home(): \Illuminate\Contracts\View\View
    {
        $this->logger->info('Home page accessed');
        return view('pages.home');
    }

    public function users(): \Illuminate\Contracts\View\View
    {
        $this->logger->info('Users page accessed');
        $users = [
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com', 'role' => 'Admin'],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com', 'role' => 'Editor'],
            ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com', 'role' => 'Viewer'],
        ];
        return view('pages.users', ['users' => $users]);
    }

    public function contact(Request $request): \Illuminate\Contracts\View\View
    {
        $errors = [];
        $success = false;
        $data = [];

        if ($request->isMethod('POST')) {
            $data = $request->only(['name', 'email', 'message']);

            if (empty($data['name'])) {
                $errors['name'] = 'Name is required.';
            }
            if (empty($data['email']) || !filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'A valid email is required.';
            }
            if (empty($data['message'])) {
                $errors['message'] = 'Message is required.';
            }

            if (empty($errors)) {
                $this->logger->info('Contact form submitted', $data);
                $success = true;
                $data = [];
            }
        }

        return view('pages.contact', [
            'errors' => $errors,
            'success' => $success,
            'data' => $data,
        ]);
    }

    public function apiPlayground(): \Illuminate\Contracts\View\View
    {
        return view('pages.api-playground');
    }

    public function errorDemo(): never
    {
        $this->logger->warning('About to trigger a demo exception');
        throw new \RuntimeException('This is a demo exception triggered from the Error Demo page');
    }
}
