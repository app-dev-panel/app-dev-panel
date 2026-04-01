<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\VarDumper\VarDumper;

#[AsCommand(
    name: 'inspect:config',
    description: 'Inspect application configuration: DI, params, phpinfo, classes, events',
)]
final class InspectConfigCommand extends Command
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $params = [],
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::OPTIONAL, 'Action: di, params, phpinfo, classes, events', 'di')
            ->addOption('group', 'g', InputOption::VALUE_OPTIONAL, 'Config group for DI action', 'di')
            ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL, 'Filter pattern for classes/services')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output raw JSON')
            ->setHelp(<<<'HELP'
                Inspect application configuration.

                View DI configuration:
                  <info>inspect:config di</info>
                  <info>inspect:config di --group=services</info>

                View application parameters:
                  <info>inspect:config params</info>

                View PHP info:
                  <info>inspect:config phpinfo</info>

                List declared classes:
                  <info>inspect:config classes</info>
                  <info>inspect:config classes --filter=App\\</info>

                View event listeners:
                  <info>inspect:config events</info>
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = (string) $input->getArgument('action');

        return match ($action) {
            'di' => $this->showDiConfig($input, $output, $io),
            'params' => $this->showParams($input, $output, $io),
            'phpinfo' => $this->showPhpinfo($input, $output, $io),
            'classes' => $this->showClasses($input, $output, $io),
            'events' => $this->showEvents($input, $output, $io),
            default => $this->handleUnknownAction($io, $action),
        };
    }

    private function showDiConfig(InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        if (!$this->container->has('config')) {
            $io->error('Config inspection requires framework integration.');
            return Command::FAILURE;
        }

        $group = (string) $input->getOption('group');
        $json = (bool) $input->getOption('json');

        $config = $this->container->get('config');
        $data = $config->get($group);
        ksort($data);

        $result = VarDumper::create($data)->asPrimitives(255);

        if ($json) {
            $this->writeJson($output, $result);
            return Command::SUCCESS;
        }

        $io->title(sprintf('DI Configuration: %s', $group));
        $this->writeJson($output, $result);

        return Command::SUCCESS;
    }

    private function showParams(InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $json = (bool) $input->getOption('json');
        $params = $this->params;
        ksort($params);

        if ($json) {
            $this->writeJson($output, $params);
            return Command::SUCCESS;
        }

        $io->title('Application Parameters');

        if ($params === []) {
            $io->info('No parameters found.');
            return Command::SUCCESS;
        }

        $this->writeJson($output, $params);

        return Command::SUCCESS;
    }

    private function showPhpinfo(InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $json = (bool) $input->getOption('json');

        ob_start();
        phpinfo();
        $phpinfo = (string) ob_get_clean();

        if ($json) {
            $output->writeln(json_encode($phpinfo, JSON_THROW_ON_ERROR));
            return Command::SUCCESS;
        }

        // Strip HTML tags for CLI output
        $text = strip_tags($phpinfo);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $output->writeln($text ?? $phpinfo);

        return Command::SUCCESS;
    }

    private function showClasses(InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $filter = $input->getOption('filter');
        $json = (bool) $input->getOption('json');

        $classes = $this->filterDeclaredClasses();

        if (is_string($filter) && $filter !== '') {
            $classes = array_values(array_filter($classes, static fn(string $class): bool => str_contains(
                strtolower($class),
                strtolower($filter),
            )));
        }

        sort($classes);

        if ($json) {
            $output->writeln(json_encode($classes, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $io->title(sprintf('Declared Classes (%d)', count($classes)));

        if ($classes === []) {
            $io->info('No classes found.');
            return Command::SUCCESS;
        }

        $io->listing($classes);

        return Command::SUCCESS;
    }

    private function showEvents(InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        if (!$this->container->has('config')) {
            $io->error('Event listener inspection requires framework integration.');
            return Command::FAILURE;
        }

        $json = (bool) $input->getOption('json');

        $config = $this->container->get('config');
        $data = [
            'common' => VarDumper::create($config->get('events'))->asPrimitives(),
            'console' => [],
            'web' => VarDumper::create($config->get('events-web'))->asPrimitives(),
        ];

        if ($json) {
            $this->writeJson($output, $data);
            return Command::SUCCESS;
        }

        $io->title('Event Listeners');
        $this->writeJson($output, $data);

        return Command::SUCCESS;
    }

    private function handleUnknownAction(SymfonyStyle $io, string $action): int
    {
        $io->error(sprintf('Unknown action "%s". Available: di, params, phpinfo, classes, events', $action));
        return Command::FAILURE;
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

    private function writeJson(OutputInterface $output, array $data): void
    {
        $output->writeln(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
