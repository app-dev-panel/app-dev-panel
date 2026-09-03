<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Config;

use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Inspector\Controller\CodeCoverageController;
use AppDevPanel\Api\Inspector\Controller\TranslationController;
use AppDevPanel\Api\Inspector\Coverage\StoredCoverageReader;
use AppDevPanel\Api\PathResolverInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionFunction;
use ReflectionProperty;

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

    public function testCodeCoverageControllerFactoryInjectsCollectorRepository(): void
    {
        $this->assertArrayHasKey(CodeCoverageController::class, $this->definitions);
        $factory = $this->definitions[CodeCoverageController::class];
        $this->assertInstanceOf(\Closure::class, $factory);

        $types = [];
        foreach (new ReflectionFunction($factory)->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType) {
                $types[] = $type->getName();
            }
        }
        $this->assertContains(
            CollectorRepositoryInterface::class,
            $types,
            'CodeCoverageController factory must inject CollectorRepositoryInterface — without it the endpoint answers 501',
        );

        $controller = $factory(
            $this->createMock(JsonResponseFactoryInterface::class),
            $this->createMock(PathResolverInterface::class),
            $this->createMock(CollectorRepositoryInterface::class),
        );

        $this->assertInstanceOf(CodeCoverageController::class, $controller);
        $this->assertInstanceOf(
            StoredCoverageReader::class,
            new ReflectionProperty(CodeCoverageController::class, 'reader')->getValue($controller),
        );
    }
}
