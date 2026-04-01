<?php

declare(strict_types=1);

namespace App\Web\Api;

use AppDevPanel\Kernel\Collector\TranslationRecord;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class IndexAction
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
        private TranslatorCollector $translatorCollector,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $this->logger->info('API index accessed');

        $this->translatorCollector->logTranslation(new TranslationRecord(
            category: 'messages',
            locale: 'en',
            message: 'welcome',
            translation: 'Welcome!',
        ));

        $this->translatorCollector->logTranslation(new TranslationRecord(
            category: 'messages',
            locale: 'de',
            message: 'welcome',
            translation: 'Willkommen!',
        ));

        $this->translatorCollector->logTranslation(new TranslationRecord(
            category: 'messages',
            locale: 'fr',
            message: 'goodbye',
            missing: true,
        ));

        return $this->responseFactory->createResponse([
            'message' => 'Welcome to the ADP Yii 3 Playground API!',
            'debug_panel' => '/debug/api/',
            'endpoints' => [
                'GET /api' => 'This page',
                'GET /api/users' => 'List users (demo)',
                'GET /api/error' => 'Trigger an exception (demo)',
            ],
        ]);
    }
}
