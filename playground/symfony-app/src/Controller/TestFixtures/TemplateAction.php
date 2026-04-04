<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[Route('/test/fixtures/template', name: 'test_template', methods: ['GET'])]
final readonly class TemplateAction
{
    public function __construct(
        private Environment $twig,
    ) {}

    public function __invoke(): JsonResponse
    {
        // Render actual Twig templates — the TwigEnvironmentProxy intercepts these
        // calls and feeds render data (template name, timing) to TemplateCollector.
        $this->twig->render('page/home.html.twig');
        $this->twig->render('page/contact.html.twig');

        return new JsonResponse(['fixture' => 'template:basic', 'status' => 'ok']);
    }
}
