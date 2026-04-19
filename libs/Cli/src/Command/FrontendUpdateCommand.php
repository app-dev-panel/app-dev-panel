<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Command;

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
    private const string ASSET_NAME = 'frontend-dist.zip';

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

                Download and install:
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
        $hasAsset = $this->findAssetUrl($release) !== null;

        $data = [
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'published_at' => $publishedAt,
            'has_frontend_asset' => $hasAsset,
            'update_available' => $currentVersion !== $latestVersion && $currentVersion !== 'unknown',
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
        ]);

        if ($data['update_available']) {
            $io->success(sprintf('Update available: %s → %s', $currentVersion, $latestVersion));
            $io->text('Run <info>frontend:update download --path=/path/to/frontend</info> to install.');
        } elseif ($currentVersion === $latestVersion) {
            $io->success('Already up to date.');
        }

        return Command::SUCCESS;
    }

    private function download(InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $path = $input->getOption('path');
        if (!is_string($path) || $path === '') {
            $io->error('Path is required. Usage: frontend:update download --path=/path/to/frontend');
            return Command::FAILURE;
        }

        try {
            $release = $this->getLatestRelease();
        } catch (\Throwable $e) {
            $io->error(sprintf('Failed to fetch release info: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $assetUrl = $this->findAssetUrl($release);
        if ($assetUrl === null) {
            $io->error(sprintf(
                'No "%s" asset found in latest release "%s".',
                self::ASSET_NAME,
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
            $this->downloadAndExtract($assetUrl, $path, $io);
        } catch (\Throwable $e) {
            $io->error(sprintf('Download failed: %s', $e->getMessage()));
            return Command::FAILURE;
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
            RequestOptions::CONNECT_TIMEOUT => 5,
        ]);

        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function findAssetUrl(array $release): ?string
    {
        foreach ($release['assets'] ?? [] as $asset) {
            if (is_array($asset) && ($asset['name'] ?? '') === self::ASSET_NAME) {
                return $asset['browser_download_url'] ?? null;
            }
        }
        return null;
    }

    private function downloadAndExtract(string $url, string $path, SymfonyStyle $io): void
    {
        $client = $this->httpClient ?? new Client();
        $tempFile = tempnam(sys_get_temp_dir(), 'adp-frontend-') . '.zip';

        try {
            $client->get($url, [
                RequestOptions::SINK => $tempFile,
                RequestOptions::HEADERS => [
                    'Accept' => 'application/octet-stream',
                    'User-Agent' => 'ADP-CLI',
                ],
                RequestOptions::TIMEOUT => 30,
                RequestOptions::CONNECT_TIMEOUT => 5,
            ]);

            if (!is_dir($path)) {
                mkdir($path, 0o777, true);
            }

            $zip = new \ZipArchive();
            $result = $zip->open($tempFile);

            if ($result !== true) {
                throw new \RuntimeException(sprintf('Failed to open zip archive (error code: %d)', $result));
            }

            $zip->extractTo($path);
            $zip->close();

            $io->text(sprintf('Extracted to %s', $path));
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    private function getCurrentVersion(InputInterface $input): string
    {
        $path = $input->getOption('path');
        if (is_string($path) && $path !== '') {
            $versionFile = rtrim($path, '/') . '/.adp-version';
            if (file_exists($versionFile)) {
                return trim((string) file_get_contents($versionFile));
            }
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
