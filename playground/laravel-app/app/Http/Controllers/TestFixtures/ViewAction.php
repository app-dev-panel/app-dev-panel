<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use AppDevPanel\Kernel\Collector\TemplateCollector;
use Illuminate\Http\JsonResponse;

final readonly class ViewAction
{
    public function __construct(
        private TemplateCollector $templateCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->templateCollector->collectRender(
            '/resources/views/pages/home.blade.php',
            '<h1>Home Page</h1><p>Welcome</p>',
            ['title' => 'Home', 'user' => 'admin'],
        );

        $this->templateCollector->collectRender(
            '/resources/views/components/header.blade.php',
            '<header>ADP</header>',
            [
                'siteName' => 'ADP',
            ],
        );

        $this->templateCollector->collectRender(
            '/resources/views/components/footer.blade.php',
            '<footer>&copy; 2026</footer>',
            ['year' => 2026],
        );

        return new JsonResponse(['fixture' => 'view:basic', 'status' => 'ok']);
    }
}
