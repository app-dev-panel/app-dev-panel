<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\View\WebView;

final readonly class ViewAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private WebView $webView,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Render actual PHP templates via yiisoft/view — the ViewEventListener
        // listens to AfterRender events and feeds render data to TemplateCollector.
        $this->webView->render(__DIR__ . '/views/home', [
            'title' => 'Home',
            'user' => 'admin',
        ]);

        $this->webView->render(__DIR__ . '/views/header', [
            'siteName' => 'ADP',
        ]);

        $this->webView->render(__DIR__ . '/views/footer', [
            'year' => 2026,
        ]);

        return $this->responseFactory->createResponse(['fixture' => 'view:basic', 'status' => 'ok']);
    }
}
