<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Config;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Inspector\Controller\TranslationController;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionFunction;

/**
 * Tests that di-api.php factory closures resolve controller dependencies correctly.
 *
 * Regression: TranslationController was constructed with `null` for the required
 * LoggerInterface argument, causing a TypeError when the translation inspector was used.
 */
final class DiApiConfigTest extends TestCase
{
    private array $definitions;

    protected function setUp(): void
    {
        $params = [
            'app-dev-panel/yii3' => [
                'enabled' => true,
                'api' => ['enabled' => true],
            ],
        ];
        $this->definitions = (static function () use ($params): array {
            return require dirname(__DIR__, 3) . '/config/di-api.php';
        })();
    }

    public function testTranslationControllerFactoryInjectsLoggerInterface(): void
    {
        $this->assertArrayHasKey(TranslationController::class, $this->definitions);
        $factory = $this->definitions[TranslationController::class];
        $this->assertInstanceOf(\Closure::class, $factory);

        $ref = new ReflectionFunction($factory);
        $types = [];
        foreach ($ref->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType) {
                $types[] = $type->getName();
            }
        }

        $this->assertContains(
            LoggerInterface::class,
            $types,
            'TranslationController factory must inject LoggerInterface — null triggers a TypeError',
        );
    }

    public function testTranslationControllerFactoryProducesValidInstance(): void
    {
        $factory = $this->definitions[TranslationController::class];

        $controller = $factory(
            $this->createMock(JsonResponseFactoryInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(ContainerInterface::class),
        );

        $this->assertInstanceOf(TranslationController::class, $controller);
    }
}
