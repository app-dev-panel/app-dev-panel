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

    /**
     * Asset names tried in order. v0.3+ ships `frontend-dist.zip` (panel +
     * toolbar combined). Older releases (≤ v0.2) only had per-package archives;
     * `panel-dist.tar.gz` is the panel half — install it as a fallback so the
     * command works against any tag, not just the new bundle.
     */
    private const array ASSET_NAMES = ['frontend-dist.zip', 'panel-dist.tar.gz'];

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
            ->addOption(
                'version',
                null,
                InputOption::VALUE_REQUIRED,
                'Release tag to pin (bypasses /releases/latest lookup)',
            )
            ->addOption(
                'token',
                null,
                InputOption::VALUE_REQUIRED,
                'GitHub API token (falls back to GITHUB_TOKEN / GH_TOKEN env vars)',
            )
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output raw JSON')
            ->setHelp(<<<'HELP'
                Check for updates and download the latest frontend build.

                Check latest version:
                  <info>frontend:update check</info>

                Download and install:
                  <info>frontend:update download --path=/path/to/frontend</info>

                Pin to a specific release (skips /releases/latest and anonymous rate limit):
                  <info>frontend:update download --version=v0.2 --path=/path/to/frontend</info>

                Authenticate to lift the 60/hour anonymous rate limit on api.github.com:
                  <info>GITHUB_TOKEN=xxx frontend:update check</info>
                  <info>frontend:update check --token=xxx</info>
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
            $release = $this->getRelease($input);
        } catch (\Throwable $e) {
            $io->error(sprintf('Failed to check for updates: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $currentVersion = $this->getCurrentVersion($input);
        $latestVersion = $release['tag_name'] ?? 'unknown';
        $publishedAt = $release['published_at'] ?? 'unknown';
        $hasAsset = $this->findAsset($release) !== null;

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
            $release = $this->getRelease($input);
        } catch (\Throwable $e) {
            $io->error(sprintf('Failed to fetch release info: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $asset = $this->findAsset($release);
        if ($asset === null) {
            $io->error(sprintf(
                'No matching asset found in latest release "%s". Tried: %s.',
                (string) ($release['tag_name'] ?? 'unknown'),
                implode(', ', self::ASSET_NAMES),
            ));
            $io->text('Available assets:');
            foreach ($release['assets'] ?? [] as $candidate) {
                if (is_array($candidate) && isset($candidate['name'])) {
                    $io->text(sprintf('  - %s', (string) $candidate['name']));
                }
            }
            return Command::FAILURE;
        }

        $io->text(sprintf('Downloading %s (%s)...', $asset['name'], (string) ($release['tag_name'] ?? 'latest')));

        try {
            $this->downloadAndExtract($asset['url'], $asset['name'], $path, $io);
        } catch (\Throwable $e) {
            $io->error(sprintf('Download failed: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $this->saveVersionFile($path, (string) ($release['tag_name'] ?? 'unknown'));

        $io->success(sprintf('Frontend updated to %s at %s', (string) ($release['tag_name'] ?? 'unknown'), $path));

        return Command::SUCCESS;
    }

    private function getRelease(InputInterface $input): array
    {
        $version = $input->getOption('version');
        $version = is_string($version) && $version !== '' ? $version : null;

        $endpoint = $version !== null
            ? sprintf('%s/repos/%s/releases/tags/%s', self::GITHUB_API, self::REPO, rawurlencode($version))
            : sprintf('%s/repos/%s/releases/latest', self::GITHUB_API, self::REPO);

        $headers = [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'ADP-CLI',
        ];
        $token = $this->resolveToken($input);
        if ($token !== null) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $client = $this->httpClient ?? new Client();
        $response = $client->get($endpoint, [
            RequestOptions::HEADERS => $headers,
            RequestOptions::TIMEOUT => 10,
            RequestOptions::CONNECT_TIMEOUT => 5,
        ]);

        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function resolveToken(InputInterface $input): ?string
    {
        $token = $input->getOption('token');
        if (is_string($token) && $token !== '') {
            return $token;
        }
        foreach (['GITHUB_TOKEN', 'GH_TOKEN'] as $env) {
            $value = getenv($env);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }
        return null;
    }

    /**
     * @return array{name: string, url: string}|null
     */
    private function findAsset(array $release): ?array
    {
        $assets = $release['assets'] ?? [];
        foreach (self::ASSET_NAMES as $wantedName) {
            foreach ($assets as $asset) {
                if (!is_array($asset) || ($asset['name'] ?? '') !== $wantedName) {
                    continue;
                }
                $url = $asset['browser_download_url'] ?? null;
                if (!is_string($url) || $url === '') {
                    continue;
                }
                return ['name' => $wantedName, 'url' => $url];
            }
        }
        return null;
    }

    private function downloadAndExtract(string $url, string $assetName, string $path, SymfonyStyle $io): void
    {
        $client = $this->httpClient ?? new Client();
        $extension = str_ends_with($assetName, '.tar.gz') ? '.tar.gz' : '.zip';
        $tempFile = tempnam(sys_get_temp_dir(), 'adp-frontend-') . $extension;

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

            if ($extension === '.tar.gz') {
                $this->extractTarGz($tempFile, $path);
            } else {
                $this->extractZip($tempFile, $path);
            }

            $io->text(sprintf('Extracted to %s', $path));
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    private function extractZip(string $archive, string $path): void
    {
        $zip = new \ZipArchive();
        $result = $zip->open($archive);
        if ($result !== true) {
            throw new \RuntimeException(sprintf('Failed to open zip archive (error code: %d)', $result));
        }
        $zip->extractTo($path);
        $zip->close();
    }

    private function extractTarGz(string $archive, string $path): void
    {
        // PharData::decompress writes a sibling .tar; extract that and clean up.
        $phar = new \PharData($archive);
        $tarPath = $archive . '.tar';
        try {
            $phar->decompress();
            $tarPhar = new \PharData($tarPath);
            $tarPhar->extractTo($path, null, true);
        } finally {
            // Force PharData destructors to release the files before unlink on Windows.
            unset($phar, $tarPhar);
            if (file_exists($tarPath)) {
                @unlink($tarPath);
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
