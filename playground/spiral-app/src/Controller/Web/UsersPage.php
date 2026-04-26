<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Application\Layout;
use App\Application\TracingPdo;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class UsersPage
{
    private const SEED = [
        ['Alice Smith',   'alice@adp.test',   'admin'],
        ['Bob Jones',     'bob@adp.test',     'editor'],
        ['Charlie Brown', 'charlie@adp.test', 'viewer'],
        ['Diana Prince',  'diana@adp.test',   'editor'],
        ['Ethan Hunt',    'ethan@adp.test',   'viewer'],
    ];

    public function __construct(
        private readonly Layout $layout,
        private readonly LoggerInterface $logger,
        private readonly TracingPdo $pdo,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $this->ensureSchema();
        $users = $this->fetchAll();

        $this->logger->info('Users page rendered', ['count' => count($users)]);

        $rows = '';
        foreach ($users as $user) {
            $rows .= sprintf(
                '<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                (int) $user['id'],
                htmlspecialchars((string) $user['name']),
                htmlspecialchars((string) $user['email']),
                htmlspecialchars((string) $user['role']),
            );
        }

        $content = <<<HTML
            <div class="page-header">
                <h1>Users</h1>
                <p>Each visit runs a small set of SQL queries (CREATE / INSERT × 5 / SELECT) against an
                in-memory SQLite database. Open the Debug Panel and look at the Database collector for
                this request.</p>
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

    private function ensureSchema(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, role TEXT)',
        );

        // SQLite is in-memory and dies with the request, so we always reseed.
        $insert = $this->pdo->prepare('INSERT INTO users (name, email, role) VALUES (?, ?, ?)');
        foreach (self::SEED as [$name, $email, $role]) {
            $insert->execute([$name, $email, $role]);
        }
    }

    /**
     * @return list<array{id: int|string, name: string, email: string, role: string}>
     */
    private function fetchAll(): array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, role FROM users ORDER BY id ASC');
        $stmt->execute();
        /** @var list<array{id: int|string, name: string, email: string, role: string}> $rows */
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $rows;
    }
}
