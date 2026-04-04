<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[Route('/test/fixtures/view', name: 'test_view', methods: ['GET'])]
final readonly class ViewAction
{
    public function __construct(
        private Environment $twig,
    ) {}

    public function __invoke(): JsonResponse
    {
        // Render actual Twig templates with context variables — the TwigEnvironmentProxy
        // intercepts these calls and feeds render data (template, output, parameters,
        // timing) to TemplateCollector.
        $this->twig->render('page/home.html.twig');
        $this->twig->render('page/contact.html.twig');
        $this->twig->render('page/users.html.twig', ['users' => [
            ['id' => 1, 'name' => 'Admin', 'email' => 'admin@example.com'],
        ]]);

        return new JsonResponse(['fixture' => 'view:basic', 'status' => 'ok']);
    }
}
