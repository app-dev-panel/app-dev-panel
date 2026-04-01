<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use AppDevPanel\Kernel\Collector\TemplateCollector;
use Illuminate\Http\JsonResponse;

final readonly class TemplateAction
{
    public function __construct(
        private TemplateCollector $templateCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->templateCollector->logRender('pages/home.blade.php', 0.0125);
        $this->templateCollector->logRender('components/header.blade.php', 0.0032);
        $this->templateCollector->logRender('components/footer.blade.php', 0.0018);

        return new JsonResponse(['fixture' => 'template:basic', 'status' => 'ok']);
    }
}
