<?php

declare(strict_types=1);

namespace App\Web\UsersPage;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final readonly class Action
{
    public function __construct(
        private ViewRenderer $viewRenderer,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $users = [
            [
                'id' => 1,
                'name' => 'Alice Johnson',
                'email' => 'alice@example.com',
                'role' => 'admin',
                'status' => 'active',
            ],
            ['id' => 2, 'name' => 'Bob Smith', 'email' => 'bob@example.com', 'role' => 'editor', 'status' => 'active'],
            [
                'id' => 3,
                'name' => 'Charlie Brown',
                'email' => 'charlie@example.com',
                'role' => 'viewer',
                'status' => 'inactive',
            ],
            [
                'id' => 4,
                'name' => 'Diana Prince',
                'email' => 'diana@example.com',
                'role' => 'editor',
                'status' => 'active',
            ],
            ['id' => 5, 'name' => 'Eve Wilson', 'email' => 'eve@example.com', 'role' => 'viewer', 'status' => 'active'],
        ];

        return $this->viewRenderer->render(__DIR__ . '/template', ['users' => $users]);
    }
}
