<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\ViewCollector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/view', name: 'test_view', methods: ['GET'])]
final readonly class ViewAction
{
    public function __construct(
        private ViewCollector $viewCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->viewCollector->collectRender('/templates/pages/home.html.twig', '<h1>Home Page</h1><p>Welcome</p>', [
            'title' => 'Home',
            'user' => 'admin',
        ]);

        $this->viewCollector->collectRender('/templates/components/header.html.twig', '<header>ADP</header>', [
            'siteName' => 'ADP',
        ]);

        $this->viewCollector->collectRender('/templates/components/footer.html.twig', '<footer>&copy; 2026</footer>', [
            'year' => 2026,
        ]);

        return new JsonResponse(['fixture' => 'view:basic', 'status' => 'ok']);
    }
}
