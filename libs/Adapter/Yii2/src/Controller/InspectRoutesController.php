<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Controller;

use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use Yiisoft\VarDumper\VarDumper;

/**
 * Inspect application routes.
 *
 * Yii 2 equivalent of the Symfony Console-based InspectRoutesCommand from libs/Cli.
 */
final class InspectRoutesController extends Controller
{
    public $defaultAction = 'list';

    public function __construct(
        $id,
        $module,
        private readonly ?object $routeCollection = null,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * List all registered routes.
     *
     * @param bool $json Output raw JSON.
     */
    public function actionList(bool $json = false): int
    {
        if ($this->routeCollection === null) {
            Console::stderr(Console::ansiFormat("Route inspection requires framework integration.\n", [
                Console::FG_RED,
            ]));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $routes = [];
        foreach ($this->routeCollection->getRoutes() as $route) {
            $data = $route->__debugInfo();
            $routes[] = [
                'name' => $data['name'],
                'pattern' => $data['pattern'],
                'methods' => $data['methods'],
            ];
        }

        $result = VarDumper::create($routes)->asPrimitives(5);

        if ($json) {
            Console::stdout(
                json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            );
            return ExitCode::OK;
        }

        if ($routes === []) {
            Console::stdout(Console::ansiFormat("No routes found.\n", [Console::FG_YELLOW]));
            return ExitCode::OK;
        }

        Console::stdout(Console::ansiFormat(sprintf("Application Routes (%d)\n", count($routes)), [Console::BOLD]));
        Console::stdout(str_repeat('=', 80) . "\n");
        Console::stdout(Console::ansiFormat(
            sprintf("%-30s  %-15s  %s\n", 'Name', 'Methods', 'Pattern'),
            [Console::BOLD],
        ));
        Console::stdout(str_repeat('-', 80) . "\n");

        foreach ($routes as $route) {
            $methods = is_array($route['methods'])
                ? implode('|', $route['methods'])
                : (string) ($route['methods'] ?? 'ANY');
            Console::stdout(sprintf(
                "%-30s  %-15s  %s\n",
                (string) ($route['name'] ?? '—'),
                $methods,
                (string) ($route['pattern'] ?? '—'),
            ));
        }

        return ExitCode::OK;
    }
}
