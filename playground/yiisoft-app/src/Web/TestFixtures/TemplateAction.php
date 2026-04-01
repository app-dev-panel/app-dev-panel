<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use AppDevPanel\Kernel\Collector\TemplateCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class TemplateAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private TemplateCollector $templateCollector,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->templateCollector->logRender('site/index.php', 0.0125);
        $this->templateCollector->logRender('layout/header.php', 0.0032);
        $this->templateCollector->logRender('layout/footer.php', 0.0018);

        return $this->responseFactory->createResponse(['fixture' => 'template:basic', 'status' => 'ok']);
    }
}
