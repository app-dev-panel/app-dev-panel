<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Command;

use AppDevPanel\Kernel\Inspector\Primitives;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'inspect:routes', description: 'Inspect application routes: list all routes, check route matching')]
final class InspectRoutesCommand extends Command
{
    private const array HTTP_METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];

    public function __construct(
        private readonly ?object $routeCollection = null,
        private readonly ?object $urlMatcher = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::OPTIONAL, 'Action: list, check', 'list')
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to check (for check action)')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output raw JSON')
            ->setHelp(<<<'HELP'
                Inspect application routes.

                List all routes:
                  <info>inspect:routes</info>
                  <info>inspect:routes list</info>

                Check route matching:
                  <info>inspect:routes check /api/users</info>
                  <info>inspect:routes check "POST /api/users"</info>
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = (string) $input->getArgument('action');

        return match ($action) {
            'list' => $this->listRoutes($input, $output, $io),
            'check' => $this->checkRoute($input, $output, $io),
            default => $this->handleUnknownAction($io, $action),
        };
    }

    private function listRoutes(InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        if ($this->routeCollection === null) {
            $io->error('Route inspection requires framework integration.');
            return Command::FAILURE;
        }

        $json = (bool) $input->getOption('json');

        $routes = [];
        foreach ($this->routeCollection->getRoutes() as $route) {
            $data = $route->__debugInfo();
            $routes[] = [
                'name' => $data['name'],
                'hosts' => $data['hosts'],
                'pattern' => $data['pattern'],
                'methods' => $data['methods'],
                'defaults' => $data['defaults'],
                'override' => $data['override'],
                'middlewares' => $data['middlewares'] ?? $data['middlewareDefinitions'] ?? [],
            ];
        }

        $result = Primitives::dump($routes, 5);

        if ($json) {
            $output->writeln(json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return Command::SUCCESS;
        }

        if ($routes === []) {
            $io->info('No routes found.');
            return Command::SUCCESS;
        }

        $io->title(sprintf('Application Routes (%d)', count($routes)));

        $rows = [];
        foreach ($routes as $route) {
            $methods = is_array($route['methods'])
                ? implode('|', $route['methods'])
                : (string) ($route['methods'] ?? 'ANY');
            $rows[] = [
                (string) ($route['name'] ?? '—'),
                $methods,
                (string) ($route['pattern'] ?? '—'),
            ];
        }

        $io->table(['Name', 'Methods', 'Pattern'], $rows);

        return Command::SUCCESS;
    }

    private function checkRoute(InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        if ($this->urlMatcher === null) {
            $io->error('Route checking requires framework integration.');
            return Command::FAILURE;
        }

        $path = $input->getArgument('path');
        if (!is_string($path) || $path === '') {
            $io->error('Path is required. Usage: inspect:routes check /path');
            return Command::FAILURE;
        }

        $json = (bool) $input->getOption('json');
        $path = trim($path);

        $method = 'GET';
        if (str_contains($path, ' ')) {
            [$possibleMethod, $restPath] = explode(' ', $path, 2);
            if (in_array($possibleMethod, self::HTTP_METHODS, true)) {
                $method = $possibleMethod;
                $path = $restPath;
            }
        }

        $serverRequest = new \GuzzleHttp\Psr7\ServerRequest($method, $path);
        $result = $this->urlMatcher->match($serverRequest);

        if (!$result->isSuccess()) {
            if ($json) {
                $output->writeln(json_encode(['result' => false], JSON_THROW_ON_ERROR));
            } else {
                $io->warning(sprintf('No route matched for %s %s', $method, $path));
            }
            return Command::SUCCESS;
        }

        $route = $result->route();
        $reflection = new \ReflectionObject($route);
        $propertyName = $reflection->hasProperty('middlewareDefinitions') ? 'middlewareDefinitions' : 'middlewares';
        $property = $reflection->getProperty($propertyName);
        $middlewareDefinitions = $property->getValue($route);
        $action = end($middlewareDefinitions);

        $data = [
            'result' => true,
            'action' => $action,
        ];

        if ($json) {
            $output->writeln(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $io->success(sprintf('Route matched: %s %s', $method, $path));
            $io->text(sprintf('Action: %s', is_string($action) ? $action : json_encode($action, JSON_THROW_ON_ERROR)));
        }

        return Command::SUCCESS;
    }

    private function handleUnknownAction(SymfonyStyle $io, string $action): int
    {
        $io->error(sprintf('Unknown action "%s". Available: list, check', $action));
        return Command::FAILURE;
    }
}
