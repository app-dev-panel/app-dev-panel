<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Application\Layout;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class UsersPage
{
    private const USERS = [
        ['id' => 1, 'name' => 'Alice Smith', 'email' => 'alice@adp.test', 'role' => 'admin'],
        ['id' => 2, 'name' => 'Bob Jones', 'email' => 'bob@adp.test', 'role' => 'editor'],
        ['id' => 3, 'name' => 'Charlie Brown', 'email' => 'charlie@adp.test', 'role' => 'viewer'],
        ['id' => 4, 'name' => 'Diana Prince', 'email' => 'diana@adp.test', 'role' => 'editor'],
        ['id' => 5, 'name' => 'Ethan Hunt', 'email' => 'ethan@adp.test', 'role' => 'viewer'],
    ];

    public function __construct(
        private readonly Layout $layout,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $this->logger->info('Users page rendered', ['count' => count(self::USERS)]);

        $rows = '';
        foreach (self::USERS as $user) {
            $rows .= sprintf(
                '<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $user['id'],
                htmlspecialchars($user['name']),
                htmlspecialchars($user['email']),
                htmlspecialchars($user['role']),
            );
        }

        $content = <<<HTML
            <div class="page-header">
                <h1>Users</h1>
                <p>Server-rendered table — each visit emits a log entry visible in ADP.</p>
            </div>
            <div class="card">
                <table>
                    <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr></thead>
                    <tbody>{$rows}</tbody>
                </table>
            </div>
            HTML;

        return new Response(200)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody(Stream::create($this->layout->render('Users', $content, '/users')));
    }
}
