<?php

declare(strict_types=1);

namespace App\Controller\Web;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;

final class OpenApiPage
{
    public function __invoke(): ResponseInterface
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'ADP Spiral Playground',
                'version' => '1.0.0',
                'description' => 'Test fixtures and demo endpoints served by the Spiral playground.',
            ],
            'paths' => [
                '/users' => ['get' => ['summary' => 'List users', 'responses' => ['200' => ['description' => 'OK']]]],
                '/contact' => ['post' => [
                    'summary' => 'Submit contact form',
                    'responses' => ['200' => ['description' => 'OK']],
                ]],
                '/test/fixtures/logs' => ['get' => [
                    'summary' => 'Emit sample log entries',
                    'responses' => ['200' => ['description' => 'OK']],
                ]],
                '/test/fixtures/events' => ['get' => [
                    'summary' => 'Dispatch sample events',
                    'responses' => ['200' => ['description' => 'OK']],
                ]],
                '/test/fixtures/exception' => ['get' => [
                    'summary' => 'Throw a runtime exception',
                    'responses' => ['500' => ['description' => 'Intentional error']],
                ]],
                '/debug' => ['get' => [
                    'summary' => 'Debug panel SPA',
                    'responses' => ['200' => ['description' => 'OK']],
                ]],
            ],
        ];

        return new Response(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Stream::create(json_encode($spec, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)));
    }
}
