<?php

declare(strict_types=1);

namespace App\Web\Api;

use AppDevPanel\Kernel\Collector\TranslationRecord;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

#[OA\Info(
    version: '1.0.0',
    title: 'ADP Yii 3 Playground API',
    description: 'Demo API for the ADP Yii 3 Playground application.',
)]
final readonly class IndexAction
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
        private TranslatorCollector $translatorCollector,
    ) {}

    #[OA\Get(
        path: '/api',
        summary: 'API index',
        tags: ['General'],
        responses: [
            new OA\Response(response: 200, description: 'API information', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'debug_panel', type: 'string'),
                new OA\Property(property: 'endpoints', type: 'object'),
            ])),
        ],
    )]
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
            'debug_panel' => '/debug/',
            'endpoints' => [
                'GET /api' => 'This page',
                'GET /api/users' => 'List users (demo)',
                'GET /api/error' => 'Trigger an exception (demo)',
            ],
        ]);
    }
}
