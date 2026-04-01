<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\TemplateCollector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/template', name: 'test_template', methods: ['GET'])]
final readonly class TemplateAction
{
    public function __construct(
        private TemplateCollector $templateCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->templateCollector->logRender('pages/home.html.twig', 0.0125);
        $this->templateCollector->logRender('components/header.html.twig', 0.0032);
        $this->templateCollector->logRender('components/footer.html.twig', 0.0018);

        return new JsonResponse(['fixture' => 'template:basic', 'status' => 'ok']);
    }
}
