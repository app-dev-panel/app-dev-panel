<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Composer;

use AppDevPanel\Cli\ApplicationFactory;
use Composer\IO\IOInterface;

final class FrontendInstaller
{
    private const string GITHUB_API = 'https://api.github.com';
    private const string REPO = 'app-dev-panel/app-dev-panel';
    private const string PANEL_ASSET = 'panel-dist.tar.gz';
    private const string TOOLBAR_ASSET = 'toolbar-dist.tar.gz';

    public function __construct(
        private readonly IOInterface $io,
    ) {}

    public function install(): void
    {
        $frontendPath = ApplicationFactory::getDefaultFrontendPath();
        $versionFile = $frontendPath . '/.adp-version';

        if (file_exists($versionFile)) {
            $this->io->write(
                '<info>ADP frontend already installed (' . trim((string) file_get_contents($versionFile)) . ')</info>',
            );
            return;
        }

        $this->io->write('<info>ADP: Downloading latest frontend build...</info>');

        try {
            $release = $this->getLatestRelease();
        } catch (\Throwable $e) {
            $this->io->writeError('<warning>ADP: Failed to fetch release info: ' . $e->getMessage() . '</warning>');
            $this->io->writeError(
                '<warning>ADP: Run "vendor/bin/adp frontend:update download" manually to install the frontend.</warning>',
            );
            return;
        }

        $panelUrl = $this->findAssetUrl($release, self::PANEL_ASSET);
        if ($panelUrl === null) {
            $this->io->writeError(
                '<warning>ADP: No panel build found in latest release. Run "vendor/bin/adp frontend:update download" manually.</warning>',
            );
            return;
        }

        try {
            $this->downloadAndExtract($panelUrl, $frontendPath);
        } catch (\Throwable $e) {
            $this->io->writeError('<warning>ADP: Frontend download failed: ' . $e->getMessage() . '</warning>');
            $this->io->writeError('<warning>ADP: Run "vendor/bin/adp frontend:update download" manually.</warning>');
            return;
        }

        // Download toolbar if available
        $toolbarUrl = $this->findAssetUrl($release, self::TOOLBAR_ASSET);
        if ($toolbarUrl !== null) {
            try {
                $this->downloadAndExtract($toolbarUrl, $frontendPath . '/toolbar');
            } catch (\Throwable) {
                // Toolbar is optional
            }
        }

        $version = (string) ($release['tag_name'] ?? 'unknown');
        file_put_contents($versionFile, $version);

        $this->io->write('<info>ADP: Frontend ' . $version . ' installed to ' . $frontendPath . '</info>');
    }

    private function getLatestRelease(): array
    {
        $url = sprintf('%s/repos/%s/releases/latest', self::GITHUB_API, self::REPO);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'Accept: application/vnd.github.v3+json',
                    'User-Agent: ADP-Composer-Plugin',
                ],
                'timeout' => 10,
            ],
        ]);

        $response = file_get_contents($url, false, $context);
        if ($response === false) {
            throw new \RuntimeException('Failed to fetch release info from GitHub');
        }

        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
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

    private function downloadAndExtract(string $url, string $targetPath): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'adp-frontend-') . '.tar.gz';

        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'Accept: application/octet-stream',
                        'User-Agent: ADP-Composer-Plugin',
                    ],
                    'timeout' => 120,
                    'follow_location' => true,
                ],
            ]);

            $content = file_get_contents($url, false, $context);
            if ($content === false) {
                throw new \RuntimeException('Failed to download: ' . $url);
            }
            file_put_contents($tempFile, $content);

            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0o777, true);
            }

            $phar = new \PharData($tempFile);
            $phar->extractTo($targetPath, null, true);

            // The archive contains a dist/ directory — move contents up if it exists
            $distDir = $targetPath . '/dist';
            if (is_dir($distDir)) {
                $this->moveContents($distDir, $targetPath);
                rmdir($distDir);
            }
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
}
