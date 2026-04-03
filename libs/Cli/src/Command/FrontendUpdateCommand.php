<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Command;

use AppDevPanel\Cli\ApplicationFactory;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'frontend:update', description: 'Check for updates and download the latest frontend build')]
final class FrontendUpdateCommand extends Command
{
    private const string GITHUB_API = 'https://api.github.com';
    private const string REPO = 'app-dev-panel/app-dev-panel';
    private const string PANEL_ASSET = 'panel-dist.tar.gz';
    private const string TOOLBAR_ASSET = 'toolbar-dist.tar.gz';

    public function __construct(
        private readonly ?Client $httpClient = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::OPTIONAL, 'Action: check, download', 'check')
            ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'Path to install frontend assets')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output raw JSON')
            ->setHelp(<<<'HELP'
                Check for updates and download the latest frontend build.

                Check latest version:
                  <info>frontend:update check</info>

                Download and install to default path:
                  <info>frontend:update download</info>

                Download and install to custom path:
                  <info>frontend:update download --path=/path/to/frontend</info>
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = (string) $input->getArgument('action');

        return match ($action) {
            'check' => $this->check($input, $output, $io),
            'download' => $this->download($input, $output, $io),
            default => $this->handleUnknownAction($io, $action),
        };
    }

    private function check(InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $json = (bool) $input->getOption('json');

        try {
            $release = $this->getLatestRelease();
        } catch (\Throwable $e) {
            $io->error(sprintf('Failed to check for updates: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $currentVersion = $this->getCurrentVersion($input);
        $latestVersion = $release['tag_name'] ?? 'unknown';
        $publishedAt = $release['published_at'] ?? 'unknown';
        $hasAsset = $this->findAssetUrl($release, self::PANEL_ASSET) !== null;

        $data = [
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'published_at' => $publishedAt,
            'has_frontend_asset' => $hasAsset,
            'update_available' => $currentVersion !== $latestVersion && $currentVersion !== 'unknown',
            'frontend_path' => $this->resolvePath($input),
        ];

        if ($json) {
            $output->writeln(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return Command::SUCCESS;
        }

        $io->title('Frontend Update Check');
        $io->table(['', ''], [
            ['Current version', $currentVersion],
            ['Latest version', $latestVersion],
            ['Published at', $publishedAt],
            ['Frontend asset available', $hasAsset ? 'Yes' : 'No'],
            ['Frontend path', $data['frontend_path']],
        ]);

        if ($data['update_available']) {
            $io->success(sprintf('Update available: %s → %s', $currentVersion, $latestVersion));
            $io->text('Run <info>frontend:update download</info> to install.');
        } elseif ($currentVersion === $latestVersion) {
            $io->success('Already up to date.');
        }

        return Command::SUCCESS;
    }

    private function download(InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $path = $this->resolvePath($input);

        try {
            $release = $this->getLatestRelease();
        } catch (\Throwable $e) {
            $io->error(sprintf('Failed to fetch release info: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $panelUrl = $this->findAssetUrl($release, self::PANEL_ASSET);
        if ($panelUrl === null) {
            $io->error(sprintf(
                'No "%s" asset found in latest release "%s".',
                self::PANEL_ASSET,
                (string) ($release['tag_name'] ?? 'unknown'),
            ));
            $io->text('Available assets:');
            foreach ($release['assets'] ?? [] as $asset) {
                if (is_array($asset) && isset($asset['name'])) {
                    $io->text(sprintf('  - %s', (string) $asset['name']));
                }
            }
            return Command::FAILURE;
        }

        $io->text(sprintf('Downloading %s...', (string) ($release['tag_name'] ?? 'latest')));

        try {
            $this->downloadAndExtract($panelUrl, $path, $io);
        } catch (\Throwable $e) {
            $io->error(sprintf('Download failed: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        // Download toolbar if available
        $toolbarUrl = $this->findAssetUrl($release, self::TOOLBAR_ASSET);
        if ($toolbarUrl !== null) {
            $io->text('Downloading toolbar...');
            try {
                $this->downloadAndExtract($toolbarUrl, $path . '/toolbar', $io);
            } catch (\Throwable) {
                $io->warning('Toolbar download failed (optional component).');
            }
        }

        $this->saveVersionFile($path, (string) ($release['tag_name'] ?? 'unknown'));

        $io->success(sprintf('Frontend updated to %s at %s', (string) ($release['tag_name'] ?? 'unknown'), $path));

        return Command::SUCCESS;
    }

    private function getLatestRelease(): array
    {
        $client = $this->httpClient ?? new Client();
        $response = $client->get(sprintf('%s/repos/%s/releases/latest', self::GITHUB_API, self::REPO), [
            RequestOptions::HEADERS => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'ADP-CLI',
            ],
            RequestOptions::TIMEOUT => 10,
        ]);

        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function findAssetUrl(array $release, string $assetName): ?string
    {
        foreach ($release['assets'] ?? [] as $asset) {
            if (is_array($asset) && ($asset['name'] ?? '') === $assetName) {
                return $asset['browser_download_url'] ?? null;
            }
        }
        return null;
    }

    private function downloadAndExtract(string $url, string $path, SymfonyStyle $io): void
    {
        $client = $this->httpClient ?? new Client();
        $tempFile = tempnam(sys_get_temp_dir(), 'adp-frontend-') . '.tar.gz';

        try {
            $client->get($url, [
                RequestOptions::SINK => $tempFile,
                RequestOptions::HEADERS => [
                    'Accept' => 'application/octet-stream',
                    'User-Agent' => 'ADP-CLI',
                ],
                RequestOptions::TIMEOUT => 120,
            ]);

            if (!is_dir($path)) {
                mkdir($path, 0o777, true);
            }

            $phar = new \PharData($tempFile);
            $phar->extractTo($path, null, true);

            // The archive contains a dist/ directory — move contents up if present
            $distDir = $path . '/dist';
            if (is_dir($distDir)) {
                $this->moveContents($distDir, $path);
                rmdir($distDir);
            }

            $io->text(sprintf('Extracted to %s', $path));
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    private function moveContents(string $source, string $target): void
    {
        $iterator = new \DirectoryIterator($source);
        foreach ($iterator as $item) {
            if ($item->isDot()) {
                continue;
            }
            $targetItem = $target . '/' . $item->getFilename();
            if (file_exists($targetItem)) {
                if (is_dir($targetItem) && !is_link($targetItem)) {
                    $this->removeDirectory($targetItem);
                } else {
                    unlink($targetItem);
                }
            }
            rename($item->getPathname(), $targetItem);
        }
    }

    private function removeDirectory(string $dir): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($dir);
    }

    private function resolvePath(InputInterface $input): string
    {
        $path = $input->getOption('path');
        if (is_string($path) && $path !== '') {
            return $path;
        }

        return ApplicationFactory::getDefaultFrontendPath();
    }

    private function getCurrentVersion(InputInterface $input): string
    {
        $path = $this->resolvePath($input);
        $versionFile = rtrim($path, '/') . '/.adp-version';
        if (file_exists($versionFile)) {
            return trim((string) file_get_contents($versionFile));
        }

        return 'unknown';
    }

    private function saveVersionFile(string $path, string $version): void
    {
        file_put_contents(rtrim($path, '/') . '/.adp-version', $version);
    }

    private function handleUnknownAction(SymfonyStyle $io, string $action): int
    {
        $io->error(sprintf('Unknown action "%s". Available: check, download', $action));
        return Command::FAILURE;
    }
}
