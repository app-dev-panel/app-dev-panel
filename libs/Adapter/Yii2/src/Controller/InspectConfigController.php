<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Controller;

use Psr\Container\ContainerInterface;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use Yiisoft\VarDumper\VarDumper;

/**
 * Inspect application configuration.
 *
 * Yii 2 equivalent of the Symfony Console-based InspectConfigCommand from libs/Cli.
 */
final class InspectConfigController extends Controller
{
    public $defaultAction = 'params';

    public function __construct(
        $id,
        $module,
        private readonly ContainerInterface $container,
        private readonly array $params = [],
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * View DI configuration.
     *
     * @param string $group Config group.
     * @param bool $json Output raw JSON.
     */
    public function actionDi(string $group = 'di', bool $json = false): int
    {
        if (!$this->container->has('config')) {
            Console::stderr(Console::ansiFormat("Config inspection requires framework integration.\n", [
                Console::FG_RED,
            ]));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $config = $this->container->get('config');
        $data = $config->get($group);
        ksort($data);

        $result = VarDumper::create($data)->asPrimitives(255);

        if (!$json) {
            Console::stdout(Console::ansiFormat(sprintf("DI Configuration: %s\n", $group), [Console::BOLD]));
            Console::stdout(str_repeat('=', 60) . "\n");
        }

        $this->outputJson($result);
        return ExitCode::OK;
    }

    /**
     * View application parameters.
     *
     * @param bool $json Output raw JSON.
     */
    public function actionParams(bool $json = false): int
    {
        $params = $this->params;
        ksort($params);

        if (!$json) {
            Console::stdout(Console::ansiFormat("Application Parameters\n", [Console::BOLD]));
            Console::stdout(str_repeat('=', 60) . "\n");
        }

        if ($params === []) {
            Console::stdout(Console::ansiFormat("No parameters found.\n", [Console::FG_YELLOW]));
            return ExitCode::OK;
        }

        $this->outputJson($params);
        return ExitCode::OK;
    }

    /**
     * View PHP info.
     */
    public function actionPhpinfo(): int
    {
        ob_start();
        phpinfo();
        $phpinfo = (string) ob_get_clean();

        $text = strip_tags($phpinfo);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        Console::stdout($text ?? $phpinfo);

        return ExitCode::OK;
    }

    /**
     * List declared classes.
     *
     * @param string|null $filter Filter pattern.
     * @param bool $json Output raw JSON.
     */
    public function actionClasses(?string $filter = null, bool $json = false): int
    {
        $classes = $this->filterDeclaredClasses();

        if ($filter !== null && $filter !== '') {
            $classes = array_values(array_filter($classes, static fn(string $class): bool => str_contains(
                strtolower($class),
                strtolower($filter),
            )));
        }

        sort($classes);

        if ($json) {
            Console::stdout(json_encode($classes, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
            return ExitCode::OK;
        }

        Console::stdout(Console::ansiFormat(sprintf("Declared Classes (%d)\n", count($classes)), [Console::BOLD]));
        Console::stdout(str_repeat('=', 60) . "\n");

        foreach ($classes as $class) {
            Console::stdout("  {$class}\n");
        }

        return ExitCode::OK;
    }

    /**
     * View event listeners.
     *
     * @param bool $json Output raw JSON.
     */
    public function actionEvents(bool $json = false): int
    {
        if (!$this->container->has('config')) {
            Console::stderr(Console::ansiFormat("Event listener inspection requires framework integration.\n", [
                Console::FG_RED,
            ]));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $config = $this->container->get('config');
        $data = [
            'common' => VarDumper::create($config->get('events'))->asPrimitives(),
            'console' => [],
            'web' => VarDumper::create($config->get('events-web'))->asPrimitives(),
        ];

        if (!$json) {
            Console::stdout(Console::ansiFormat("Event Listeners\n", [Console::BOLD]));
            Console::stdout(str_repeat('=', 60) . "\n");
        }

        $this->outputJson($data);
        return ExitCode::OK;
    }

    /** @return list<string> */
    private function filterDeclaredClasses(): array
    {
        $inspected = [...get_declared_classes(), ...get_declared_interfaces()];
        $patterns = [
            static fn(string $class): bool => !str_starts_with($class, 'ComposerAutoloaderInit'),
            static fn(string $class): bool => !str_starts_with($class, 'Composer\\'),
            static fn(string $class): bool => !str_starts_with($class, 'AppDevPanel\\'),
            static fn(string $class): bool => !str_contains($class, '@anonymous'),
            static fn(string $class): bool => !is_subclass_of($class, \Throwable::class),
        ];
        foreach ($patterns as $patternFunction) {
            $inspected = array_filter($inspected, $patternFunction);
        }

        return array_values(array_filter($inspected, static function (string $className): bool {
            $class = new \ReflectionClass($className);
            return !$class->isInternal() && !$class->isAbstract() && !$class->isAnonymous();
        }));
    }

    private function outputJson(mixed $data): void
    {
        Console::stdout(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }
}
