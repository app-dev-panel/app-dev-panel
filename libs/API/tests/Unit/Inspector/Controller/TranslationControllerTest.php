<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\TranslationController;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\MessageReaderInterface;
use Yiisoft\Translator\MessageWriterInterface;

final class TranslationControllerTest extends ControllerTestCase
{
    public function testGetTranslations(): void
    {
        $reader = $this->createMock(MessageReaderInterface::class);
        $reader->method('getMessages')->willReturn(['hello' => ['message' => 'Привет']]);

        $source = $this->createCategorySource('app', $reader);
        $container = $this->container([
            'tag@translation.categorySource' => [$source],
        ]);

        $controller = $this->createController(['locale' => ['locales' => [
            'en' => 'English',
            'ru' => 'Russian',
        ]]], $container);
        $response = $controller->getTranslations($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('app', $data);
    }

    public function testGetTranslationsEmptyLocales(): void
    {
        $container = $this->container([
            'tag@translation.categorySource' => [],
        ]);

        $controller = $this->createController(['locale' => ['locales' => []]], $container);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to determine list of available locales');
        $controller->getTranslations($this->get());
    }

    public function testPutTranslation(): void
    {
        $reader = $this->createMock(MessageReaderInterface::class);
        $reader->method('getMessages')->willReturn(['hello' => ['message' => 'Hello']]);

        $writer = $this->createMock(MessageWriterInterface::class);
        $writer->expects($this->once())->method('write');

        $source = $this->createCategorySourceWithWriter('app', $reader, $writer);
        $container = $this->container([
            'tag@translation.categorySource' => [$source],
        ]);

        $controller = $this->createController(['locale' => ['locales' => ['en' => 'English']]], $container);
        $response = $controller->putTranslation($this->put([
            'category' => 'app',
            'locale' => 'en',
            'translation' => 'hello',
            'message' => 'Hi',
        ]));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testPutTranslationInvalidLocale(): void
    {
        $container = $this->container([
            'tag@translation.categorySource' => [],
        ]);

        $controller = $this->createController(['locale' => ['locales' => ['en' => 'English']]], $container);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid locale');
        $controller->putTranslation($this->put([
            'category' => 'app',
            'locale' => '../../etc',
            'translation' => 'hello',
            'message' => 'Hi',
        ]));
    }

    public function testPutTranslationInvalidCategory(): void
    {
        $reader = $this->createMock(MessageReaderInterface::class);
        $source = $this->createCategorySource('app', $reader);
        $container = $this->container([
            'tag@translation.categorySource' => [$source],
        ]);

        $controller = $this->createController(['locale' => ['locales' => ['en' => 'English']]], $container);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid category name');
        $controller->putTranslation($this->put([
            'category' => 'nonexistent',
            'locale' => 'en',
            'translation' => 'hello',
            'message' => 'Hi',
        ]));
    }

    private function createController(array $params, ?ContainerInterface $container = null): TranslationController
    {
        return new TranslationController(
            $this->createResponseFactory(),
            new NullLogger(),
            $container ?? $this->container(),
            $params,
        );
    }

    private function createCategorySource(string $name, MessageReaderInterface $reader): CategorySource
    {
        return new CategorySource($name, $reader);
    }

    private function createCategorySourceWithWriter(
        string $name,
        MessageReaderInterface $reader,
        MessageWriterInterface $writer,
    ): CategorySource {
        return new CategorySource($name, $reader, null, $writer);
    }
}
