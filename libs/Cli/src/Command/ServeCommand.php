<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'serve', description: 'Start standalone ADP API server')]
final class ServeCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Host to serve at', '127.0.0.1')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Port to serve at', '8888')
            ->addOption('storage-path', null, InputOption::VALUE_OPTIONAL, 'Debug data storage path')
            ->addOption('root-path', null, InputOption::VALUE_OPTIONAL, 'Project root path', getcwd())
            ->addOption('runtime-path', null, InputOption::VALUE_OPTIONAL, 'Runtime/temp path')
            ->addOption('frontend-path', null, InputOption::VALUE_OPTIONAL, 'Path to built frontend assets');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $host = $input->getOption('host');
        $port = $input->getOption('port');
        $storagePath = $input->getOption('storage-path') ?? sys_get_temp_dir() . '/adp';
        $rootPath = $input->getOption('root-path');
        $runtimePath = $input->getOption('runtime-path') ?? $storagePath;
        $frontendPath = $input->getOption('frontend-path');

        $routerScript = dirname(__DIR__) . '/Server/server-router.php';

        if (!file_exists($routerScript)) {
            $io->error('Server router script not found: ' . $routerScript);
            return Command::FAILURE;
        }

        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0777, true);
        }

        $phpBinary = new PhpExecutableFinder()->find();
        if ($phpBinary === false) {
            $io->error('PHP binary not found.');
            return Command::FAILURE;
        }

        $io->title('ADP Standalone Server');
        $io->success(sprintf('Starting server on http://%s:%s', $host, $port));
        $io->text([
            sprintf('Storage: %s', $storagePath),
            sprintf('Root: %s', $rootPath),
            sprintf('Frontend: %s', $frontendPath ?? '(not configured)'),
        ]);

        $env = [
            'ADP_STORAGE_PATH' => $storagePath,
            'ADP_ROOT_PATH' => $rootPath,
            'ADP_RUNTIME_PATH' => $runtimePath,
        ];
        if ($frontendPath !== null) {
            $env['ADP_FRONTEND_PATH'] = $frontendPath;
        }

        $process = new Process(
            [
                $phpBinary,
                '-S',
                "{$host}:{$port}",
                $routerScript,
            ],
            null,
            $env,
        );

        $process->setTimeout(null);

        $process->run(function (string $type, string $buffer) use ($io): void {
            $io->text(rtrim($buffer));
        });

        return $process->getExitCode() ?? Command::FAILURE;
    }
}
