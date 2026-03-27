<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use AppDevPanel\Kernel\Collector\TranslationRecord;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class TranslatorAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private TranslatorCollector $translatorCollector,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->translatorCollector->logTranslation(new TranslationRecord(
            category: 'app',
            locale: 'en',
            message: 'welcome',
            translation: 'Welcome!',
        ));

        $this->translatorCollector->logTranslation(new TranslationRecord(
            category: 'app',
            locale: 'de',
            message: 'welcome',
            translation: 'Willkommen!',
        ));

        $this->translatorCollector->logTranslation(new TranslationRecord(
            category: 'app',
            locale: 'en',
            message: 'goodbye',
            translation: 'Goodbye!',
        ));

        $this->translatorCollector->logTranslation(new TranslationRecord(
            category: 'app',
            locale: 'fr',
            message: 'welcome',
            missing: true,
        ));

        return $this->responseFactory->createResponse(['fixture' => 'translator:basic', 'status' => 'ok']);
    }
}
