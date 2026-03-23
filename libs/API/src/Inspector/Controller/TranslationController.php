<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;
use Yiisoft\VarDumper\VarDumper;

final class TranslationController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly array $params = [],
    ) {}

    public function getTranslations(ServerRequestInterface $request): ResponseInterface
    {
        $categorySources = $this->resolveCategorySources();
        if ($categorySources instanceof ResponseInterface) {
            return $categorySources;
        }

        $locales = $this->resolveLocales();
        $messages = $this->collectMessages($categorySources, $locales);

        $response = VarDumper::create($messages)->asPrimitives(255);
        return $this->responseFactory->createJsonResponse($response);
    }

    public function putTranslation(ServerRequestInterface $request): ResponseInterface
    {
        $categorySources = $this->resolveCategorySources();
        if ($categorySources instanceof ResponseInterface) {
            return $categorySources;
        }

        $body = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $locale = $body['locale'] ?? '';
        $this->validateLocale($locale);

        $categorySource = $this->findCategorySource($categorySources, $body['category'] ?? '');
        $messages = array_replace_recursive($categorySource->getMessages($locale), [
            $body['translation'] ?? '' => [
                'message' => $body['message'] ?? '',
            ],
        ]);
        $categorySource->write($locale, $messages);

        $result = [$locale => $messages];
        $response = VarDumper::create($result)->asPrimitives(255);
        return $this->responseFactory->createJsonResponse($response);
    }

    private function resolveCategorySources(): array|ResponseInterface
    {
        if (!$this->container->has('tag@translation.categorySource')) {
            return $this->responseFactory->createJsonResponse([
                'error' => 'Translation inspection requires framework integration.',
            ], 501);
        }

        return $this->container->get('tag@translation.categorySource');
    }

    private function resolveLocales(): array
    {
        /** @var string[] $locales */
        $locales = array_keys($this->params['locale']['locales'] ?? []);
        if ($locales === []) {
            throw new RuntimeException(
                'Unable to determine list of available locales. '
                . 'Make sure that "$params[\'locale\'][\'locales\']" contains all available locales.',
            );
        }
        return $locales;
    }

    private function collectMessages(array $categorySources, array $locales): array
    {
        $messages = [];
        foreach ($categorySources as $categorySource) {
            $categoryName = $categorySource->getName();

            try {
                foreach ($locales as $locale) {
                    $messages[$categoryName][$locale] = $categorySource->getMessages($locale);
                }
            } catch (Throwable $exception) {
                $this->logger->warning($exception->getMessage(), ['exception' => $exception]);
            }
        }
        return $messages;
    }

    private function validateLocale(string $locale): void
    {
        if (!preg_match('/^[a-zA-Z]{2,3}([_-][a-zA-Z0-9]{2,8})*$/', $locale)) {
            throw new InvalidArgumentException(sprintf('Invalid locale "%s".', $locale));
        }
    }

    private function findCategorySource(array $categorySources, string $categoryName): object
    {
        foreach ($categorySources as $possibleCategorySource) {
            if ($possibleCategorySource->getName() === $categoryName) {
                return $possibleCategorySource;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Invalid category name "%s". Only the following categories are available: "%s"',
            $categoryName,
            implode('", "', array_map(static fn(object $cs) => $cs->getName(), $categorySources)),
        ));
    }
}
