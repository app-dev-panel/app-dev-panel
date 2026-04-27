<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Application\Layout;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class LogDemoPage
{
    public function __construct(
        private readonly Layout $layout,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $this->logger->debug('Log Demo: debug-level example', ['page' => 'log-demo']);
        $this->logger->info('Log Demo: info-level example', ['user' => 'Alice', 'id' => 42]);
        $this->logger->notice('Log Demo: notice-level example');
        $this->logger->warning('Log Demo: warning-level example', ['threshold' => 95]);
        $this->logger->error('Log Demo: error-level example', ['code' => 'E_DEMO']);
        $this->logger->critical('Log Demo: critical-level example');

        $content = <<<'HTML'
            <div class="page-header">
                <h1>Log Demo</h1>
                <p>This page just emitted six log entries at different severity levels. Open the Debug Panel and look at the Log collector for this request.</p>
            </div>
            <div class="card">
                <p class="alert alert-info">Each visit writes 6 log entries (debug, info, notice, warning, error, critical).</p>
                <a href="/debug" target="_blank" rel="noopener" class="btn btn-primary">Open Debug Panel</a>
            </div>
            HTML;

        return new Response(200)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody(Stream::create($this->layout->render('Log Demo', $content, '/log-demo')));
    }
}
