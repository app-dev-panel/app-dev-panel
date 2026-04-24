<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Command;

use AppDevPanel\FrontendAssets\FrontendAssets;
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
    public function __construct(
        private readonly ?string $routerScript = null,
        private readonly ?PhpExecutableFinder $phpExecutableFinder = null,
    ) {
        parent::__construct();
    }

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
        $frontendPath = $input->getOption('frontend-path') ?? $this->resolveFrontendPath();

        $routerScript = $this->routerScript ?? dirname(__DIR__) . '/Server/server-router.php';

        if (!file_exists($routerScript)) {
            $io->error('Server router script not found: ' . $routerScript);
            return Command::FAILURE;
        }

        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0o777, true);
        }

        $phpBinary = ($this->phpExecutableFinder ?? new PhpExecutableFinder())->find();
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

        $command = [$phpBinary, '-S', "{$host}:{$port}"];
        // When a frontend bundle is available, point the built-in server's
        // document root at it so that `return false` from the router (signalling
        // "serve this file directly") resolves correctly.
        if ($frontendPath !== null && is_dir($frontendPath)) {
            $command[] = '-t';
            $command[] = $frontendPath;
        }
        $command[] = $routerScript;

        $process = new Process($command, null, $env);

        $process->setTimeout(null);

        $process->run(static function (string $type, string $buffer) use ($io): void {
            $io->text(rtrim($buffer));
        });

        return $process->getExitCode() ?? Command::FAILURE;
    }

    /**
     * Auto-resolve the frontend dist path from the `app-dev-panel/frontend-assets`
     * Composer package when `--frontend-path` is not supplied. Returns `null` if
     * the package is not installed or the bundle is empty, so the serve command
     * still works with API-only setups.
     */
    private function resolveFrontendPath(): ?string
    {
        if (!class_exists(FrontendAssets::class)) {
            return null;
        }

        return FrontendAssets::exists() ? FrontendAssets::path() : null;
    }
}
