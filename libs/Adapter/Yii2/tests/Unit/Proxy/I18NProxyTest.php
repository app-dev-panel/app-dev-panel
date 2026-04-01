<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Proxy;

use AppDevPanel\Adapter\Yii2\Proxy\I18NProxy;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use PHPUnit\Framework\TestCase;

final class I18NProxyTest extends TestCase
{
    private TranslatorCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new TranslatorCollector();
        $this->collector->startup();
    }

    public function testTranslateCollectsFoundTranslation(): void
    {
        $proxy = new I18NProxy([
            'translations' => [
                'yii' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'sourceLanguage' => 'en',
                    'basePath' => '@yii/messages',
                ],
                'app' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'basePath' => __DIR__ . '/fixtures/messages',
                    'sourceLanguage' => 'en',
                ],
            ],
        ]);
        $proxy->setCollector($this->collector);

        $result = $proxy->translate('app', 'hello', [], 'de');

        $this->assertSame('Hallo', $result);

        $collected = $this->collector->getCollected();
        $this->assertSame(1, $collected['totalCount']);
        $this->assertSame(0, $collected['missingCount']);
        $this->assertSame('app', $collected['translations'][0]['category']);
        $this->assertSame('de', $collected['translations'][0]['locale']);
        $this->assertSame('hello', $collected['translations'][0]['message']);
        $this->assertSame('Hallo', $collected['translations'][0]['translation']);
        $this->assertFalse($collected['translations'][0]['missing']);
    }

    public function testTranslateCollectsMissingTranslation(): void
    {
        $proxy = new I18NProxy([
            'translations' => [
                'yii' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'sourceLanguage' => 'en',
                    'basePath' => '@yii/messages',
                ],
                'app' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'basePath' => __DIR__ . '/fixtures/messages',
                    'sourceLanguage' => 'en',
                ],
            ],
        ]);
        $proxy->setCollector($this->collector);

        $result = $proxy->translate('app', 'nonexistent', [], 'de');

        // Missing translations return the original message
        $this->assertSame('nonexistent', $result);

        $collected = $this->collector->getCollected();
        $this->assertSame(1, $collected['totalCount']);
        $this->assertSame(1, $collected['missingCount']);
        $this->assertTrue($collected['translations'][0]['missing']);
        $this->assertNull($collected['translations'][0]['translation']);
    }

    public function testTranslateWithoutCollectorDoesNotFail(): void
    {
        $proxy = new I18NProxy([
            'translations' => [
                'yii' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'sourceLanguage' => 'en',
                    'basePath' => '@yii/messages',
                ],
                'app' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'basePath' => __DIR__ . '/fixtures/messages',
                    'sourceLanguage' => 'en',
                ],
            ],
        ]);

        // No collector set — should still work
        $result = $proxy->translate('app', 'hello', [], 'de');
        $this->assertSame('Hallo', $result);
    }

    public function testTranslateWithParamsFormatsFoundTranslation(): void
    {
        $proxy = $this->createProxy();
        $proxy->setCollector($this->collector);

        // Yii 2 format() uses ICU MessageFormat — {name} is replaced
        $result = $proxy->translate('app', 'hello', [], 'de');

        // The found translation 'Hallo' is formatted with the target language 'de'
        $this->assertSame('Hallo', $result);

        $collected = $this->collector->getCollected();
        $this->assertFalse($collected['translations'][0]['missing']);
        $this->assertSame('Hallo', $collected['translations'][0]['translation']);
    }

    public function testTranslateMissingFormatsWithSourceLanguage(): void
    {
        $proxy = $this->createProxy();
        $proxy->setCollector($this->collector);

        // When translation is missing, the original message is formatted with source language
        $result = $proxy->translate('app', 'nonexistent', [], 'de');

        $this->assertSame('nonexistent', $result);

        $collected = $this->collector->getCollected();
        $this->assertTrue($collected['translations'][0]['missing']);
        $this->assertNull($collected['translations'][0]['translation']);
    }

    public function testMultipleTranslationsAccumulate(): void
    {
        $proxy = $this->createProxy();
        $proxy->setCollector($this->collector);

        $proxy->translate('app', 'hello', [], 'de');
        $proxy->translate('app', 'nonexistent', [], 'de');
        $proxy->translate('app', 'hello', [], 'de');

        $collected = $this->collector->getCollected();
        $this->assertSame(3, $collected['totalCount']);
        $this->assertSame(1, $collected['missingCount']);
    }

    private function createProxy(): I18NProxy
    {
        return new I18NProxy([
            'translations' => [
                'yii' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'sourceLanguage' => 'en',
                    'basePath' => '@yii/messages',
                ],
                'app' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'basePath' => __DIR__ . '/fixtures/messages',
                    'sourceLanguage' => 'en',
                ],
            ],
        ]);
    }
}
