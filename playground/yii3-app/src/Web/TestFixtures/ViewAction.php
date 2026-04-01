<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use AppDevPanel\Kernel\Collector\TemplateCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class ViewAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private TemplateCollector $templateCollector,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->templateCollector->collectRender('/views/site/index.php', '<h1>Home Page</h1><p>Welcome</p>', [
            'title' => 'Home',
            'user' => 'admin',
        ]);

        $this->templateCollector->collectRender('/views/layout/header.php', '<header>ADP</header>', [
            'siteName' => 'ADP',
        ]);

        $this->templateCollector->collectRender('/views/layout/footer.php', '<footer>&copy; 2026</footer>', [
            'year' => 2026,
        ]);

        return $this->responseFactory->createResponse(['fixture' => 'view:basic', 'status' => 'ok']);
    }
}
