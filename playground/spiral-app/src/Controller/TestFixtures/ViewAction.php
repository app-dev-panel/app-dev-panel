<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\TemplateCollector;

final class ViewAction
{
    public function __construct(
        private readonly TemplateCollector $templates,
    ) {}

    /**
     * @return array<string, string>
     */
    public function __invoke(): array
    {
        $this->templates->beginRender('layouts/base.html.twig');
        $startedAt = microtime(true);
        $this->templates->beginRender('partials/header.html.twig');
        usleep(500);
        $this->templates->endRender('<header>Hello</header>', ['title' => 'Welcome'], 0.0005);
        $output = '<html><body><header>Hello</header><main>Body</main></body></html>';
        $this->templates->endRender(
            output: $output,
            parameters: ['user' => 'Alice', 'title' => 'Welcome'],
            renderTime: microtime(true) - $startedAt,
        );

        return ['fixture' => 'template:basic', 'status' => 'ok'];
    }
}
