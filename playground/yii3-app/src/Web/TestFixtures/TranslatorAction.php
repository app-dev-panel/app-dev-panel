<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Translator\TranslatorInterface;

final readonly class TranslatorAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private TranslatorInterface $translator,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Translate using Yii's Translator — the TranslatorInterfaceProxy intercepts
        // these calls and feeds translation data to TranslatorCollector.

        // Found: en → "Welcome!"
        $this->translator->translate('welcome', category: 'app', locale: 'en');

        // Found: de → "Willkommen!"
        $this->translator->translate('welcome', category: 'app', locale: 'de');

        // Found: en → "Goodbye!"
        $this->translator->translate('goodbye', category: 'app', locale: 'en');

        // Missing: fr has no translations
        $this->translator->translate('welcome', category: 'app', locale: 'fr');

        return $this->responseFactory->createResponse(['fixture' => 'translator:basic', 'status' => 'ok']);
    }
}
