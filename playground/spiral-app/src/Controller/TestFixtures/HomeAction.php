<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Root page — lists every fixture endpoint so a human hitting the server can click around.
 */
final class HomeAction
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(ServerRequestInterface $request): array
    {
        return [
            'app' => 'Spiral ADP playground',
            'debug_panel' => '/debug',
            'fixtures' => [
                '/test/fixtures/reset',
                '/test/fixtures/logs',
                '/test/fixtures/logs-context',
                '/test/fixtures/logs-heavy',
                '/test/fixtures/events',
                '/test/fixtures/dump',
                '/test/fixtures/timeline',
                '/test/fixtures/request-info',
                '/test/fixtures/exception',
                '/test/fixtures/exception-chained',
                '/test/fixtures/multi',
                '/test/fixtures/http-client',
            ],
        ];
    }
}
