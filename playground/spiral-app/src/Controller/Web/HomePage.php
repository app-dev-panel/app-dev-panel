<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Application\Layout;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;

final class HomePage
{
    public function __construct(
        private readonly Layout $layout,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $body = $this->layout->render(title: '', currentPath: '/', content: <<<'HTML'
            <div class="page-header">
                <h1>ADP Spiral Playground</h1>
                <p>A demo application for testing the Application Development Panel</p>
            </div>

            <div class="grid grid-2">
                <a href="/users" class="feature-card"><h3>Users</h3><p>Browse a user directory rendered server-side.</p></a>
                <a href="/contact" class="feature-card"><h3>Contact Form</h3><p>Submit a form with server-side validation.</p></a>
                <a href="/api-playground" class="feature-card"><h3>API Playground</h3><p>Send requests to API endpoints and inspect responses.</p></a>
                <a href="/error" class="feature-card"><h3>Error Demo</h3><p>Trigger an exception to test the error collector.</p></a>
                <a href="/log-demo" class="feature-card"><h3>Log Demo</h3><p>Send log messages with different severity levels and context.</p></a>
                <a href="/var-dumper" class="feature-card"><h3>Var Dumper</h3><p>Dump variables to inspect their structure in ADP.</p></a>
                <a href="/api/openapi.json" class="feature-card"><h3>OpenAPI</h3><p>Get the raw OpenAPI specification served by the playground.</p></a>
                <a href="/debug" class="feature-card" target="_blank" rel="noopener"><h3>Debug Panel</h3><p>View collected debug data in the ADP panel.</p></a>
            </div>
            HTML);

        return new Response(200)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody(Stream::create($body));
    }
}
