<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Application\Layout;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\VarDumper\VarDumper;

final class VarDumperPage
{
    public function __construct(
        private readonly Layout $layout,
    ) {}

    public function __invoke(): ResponseInterface
    {
        // Explicitly use Symfony's VarDumper so our ADP handler (set by DebugMiddleware)
        // intercepts — yiisoft/var-dumper's global dump() function shadows Symfony's.
        VarDumper::dump('string dumped from VarDumperPage');
        VarDumper::dump(['nested' => ['one' => 1, 'two' => [2, 3, 4]]]);
        VarDumper::dump(new \stdClass());

        $content = <<<'HTML'
            <div class="page-header">
                <h1>Var Dumper</h1>
                <p>Three values were dumped during this request. They show up in the VarDumper collector in the debug panel.</p>
            </div>
            <div class="card">
                <p class="alert alert-info">Every visit dumps a string, a nested array and a stdClass object.</p>
                <a href="/debug" target="_blank" rel="noopener" class="btn btn-primary">Open Debug Panel</a>
            </div>
            HTML;

        return new Response(200)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody(Stream::create($this->layout->render('Var Dumper', $content, '/var-dumper')));
    }
}
