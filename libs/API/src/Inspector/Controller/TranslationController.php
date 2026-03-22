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
        if (!$this->container->has('tag@translation.categorySource')) {
            return $this->responseFactory->createJsonResponse([
                'error' => 'Translation inspection requires framework integration.',
            ], 501);
        }

        $categorySources = $this->container->get('tag@translation.categorySource');

        /** @var string[] $locales */
        $locales = array_keys($this->params['locale']['locales'] ?? []);
        if ($locales === []) {
            throw new RuntimeException(
                'Unable to determine list of available locales. '
                . 'Make sure that "$params[\'locale\'][\'locales\']" contains all available locales.',
            );
        }
        $messages = [];
        foreach ($categorySources as $categorySource) {
            $categoryName = $categorySource->getName();

            if (!array_key_exists($categoryName, $messages)) {
                $messages[$categoryName] = [];
            }

            try {
                foreach ($locales as $locale) {
                    $messages[$categoryName][$locale] = array_merge(
                        $messages[$categoryName][$locale] ?? [],
                        $categorySource->getMessages($locale),
                    );
                }
            } catch (Throwable $exception) {
                $this->logger->warning($exception->getMessage(), ['exception' => $exception]);
            }
        }

        $response = VarDumper::create($messages)->asPrimitives(255);
        return $this->responseFactory->createJsonResponse($response);
    }

    public function putTranslation(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->container->has('tag@translation.categorySource')) {
            return $this->responseFactory->createJsonResponse([
                'error' => 'Translation inspection requires framework integration.',
            ], 501);
        }

        $categorySources = $this->container->get('tag@translation.categorySource');

        $body = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $categoryName = $body['category'] ?? '';
        $locale = $body['locale'] ?? '';
        $translationId = $body['translation'] ?? '';
        $newMessage = $body['message'] ?? '';

        if (!preg_match('/^[a-zA-Z]{2,3}([_-][a-zA-Z0-9]{2,8})*$/', $locale)) {
            throw new InvalidArgumentException(sprintf('Invalid locale "%s".', $locale));
        }

        $categorySource = null;
        foreach ($categorySources as $possibleCategorySource) {
            if ($possibleCategorySource->getName() !== $categoryName) {
                continue;
            }

            $categorySource = $possibleCategorySource;
        }
        if ($categorySource === null) {
            throw new InvalidArgumentException(sprintf(
                'Invalid category name "%s". Only the following categories are available: "%s"',
                $categoryName,
                implode('", "', array_map(static fn(object $cs) => $cs->getName(), $categorySources)),
            ));
        }
        $messages = $categorySource->getMessages($locale);
        $messages = array_replace_recursive($messages, [
            $translationId => [
                'message' => $newMessage,
            ],
        ]);
        $categorySource->write($locale, $messages);

        $result = [$locale => $messages];
        $response = VarDumper::create($result)->asPrimitives(255);
        return $this->responseFactory->createJsonResponse($response);
    }
}
