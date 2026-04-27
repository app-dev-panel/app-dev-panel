<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Application\Layout;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;

final class ApiPlaygroundPage
{
    public function __construct(
        private readonly Layout $layout,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $content = <<<'HTML'
            <div class="page-header">
                <h1>API Playground</h1>
                <p>Send requests to the fixture endpoints and inspect the JSON responses inline.</p>
            </div>
            <div class="card">
                <div class="form-group">
                    <label>Endpoint</label>
                    <select id="endpoint" class="form-control">
                        <option value="/test/fixtures/logs">GET /test/fixtures/logs</option>
                        <option value="/test/fixtures/events">GET /test/fixtures/events</option>
                        <option value="/test/fixtures/multi">GET /test/fixtures/multi</option>
                        <option value="/test/fixtures/cache">GET /test/fixtures/cache</option>
                        <option value="/test/fixtures/validator">GET /test/fixtures/validator</option>
                        <option value="/test/fixtures/translator">GET /test/fixtures/translator</option>
                        <option value="/test/fixtures/mailer">GET /test/fixtures/mailer</option>
                        <option value="/test/fixtures/queue">GET /test/fixtures/queue</option>
                        <option value="/test/fixtures/http-client">GET /test/fixtures/http-client</option>
                        <option value="/test/fixtures/exception">GET /test/fixtures/exception</option>
                    </select>
                </div>
                <button class="btn btn-primary" onclick="runApi()">Send request</button>
                <div id="result" style="margin-top:16px;"></div>
            </div>
            <script>
            async function runApi() {
                const endpoint = document.getElementById('endpoint').value;
                const result = document.getElementById('result');
                const started = performance.now();
                try {
                    const r = await fetch(endpoint);
                    const took = Math.round(performance.now() - started);
                    const text = await r.text();
                    const body = (() => { try { return JSON.stringify(JSON.parse(text), null, 2); } catch { return text; } })();
                    const statusClass = r.ok ? 'ok' : 'error';
                    result.innerHTML = `<div class="response-status ${statusClass}">Status: ${r.status} · ${took} ms</div><pre class="code-block">${body.replace(/[<>&]/g, c => ({'<':'&lt;','>':'&gt;','&':'&amp;'}[c]))}</pre>`;
                } catch (e) {
                    result.innerHTML = `<div class="alert alert-error">${e.message}</div>`;
                }
            }
            </script>
            HTML;

        return new Response(200)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody(Stream::create($this->layout->render('API Playground', $content, '/api-playground')));
    }
}
