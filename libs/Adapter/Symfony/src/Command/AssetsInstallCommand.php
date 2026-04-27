<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Command;

use AppDevPanel\FrontendAssets\FrontendAssets;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Publishes the prebuilt panel + toolbar bundle from
 * `app-dev-panel/frontend-assets` into `public/bundles/appdevpanel/`, so nginx
 * (or any web server) serves the files directly. PHP never proxies the bundle.
 *
 * Mirrors the UX of Symfony's stock `assets:install` — supports `--symlink`,
 * `--relative`, and falls back to `--copy`.
 *
 * After running, the bundle auto-detects the published copy and points the
 * panel at `/bundles/appdevpanel`; until then `panel.static_url` falls back
 * to {@see PanelConfig::DEFAULT_STATIC_URL} (the GitHub Pages CDN).
 */
#[AsCommand(
    name: 'app-dev-panel:assets:install',
    description: 'Install ADP panel + toolbar assets from app-dev-panel/frontend-assets into public/bundles/appdevpanel/',
)]
final class AssetsInstallCommand extends Command
{
    public const COMMAND_NAME = 'app-dev-panel:assets:install';
    public const PUBLIC_SUBPATH = 'bundles/appdevpanel';

    public function __construct(
        private readonly string $projectDir,
        private readonly ?Filesystem $filesystem = null,
        private readonly string $sourceDir = '',
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'symlink',
                null,
                InputOption::VALUE_NONE,
                'Symlink instead of copy (falls back to copy if symlinks fail)',
            )
            ->addOption('relative', null, InputOption::VALUE_NONE, 'Use relative symlinks (implies --symlink)')
            ->addOption(
                'public-dir',
                null,
                InputOption::VALUE_REQUIRED,
                'Override the app public directory (default: <project>/public)',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = $this->filesystem ?? new Filesystem();

        $source = $this->sourceDir !== '' ? $this->sourceDir : FrontendAssets::path();
        if (!is_file($source . '/index.html')) {
            $io->error(
                'app-dev-panel/frontend-assets is not installed or its dist/ is empty. '
                . 'Run `composer require app-dev-panel/frontend-assets` or rebuild the monorepo frontend.',
            );
            return Command::FAILURE;
        }
        $publicDir = $input->getOption('public-dir') ?? $this->projectDir . '/public';
        $target = rtrim((string) $publicDir, '/\\') . '/' . self::PUBLIC_SUBPATH;

        $relative = (bool) $input->getOption('relative');
        $symlink = $relative || (bool) $input->getOption('symlink');

        // Remove any existing install — symlink, directory, or file — so the new
        // publish is clean and the strategy switch (copy → symlink or vice versa)
        // doesn't leave a half-stale mix behind.
        if (is_link($target) || is_file($target) || is_dir($target)) {
            $fs->remove($target);
        }
        $fs->mkdir(\dirname($target));

        $method = 'copy';
        if ($symlink) {
            try {
                $fs->symlink($relative ? $fs->makePathRelative($source, \dirname($target)) : $source, $target);
                $method = $relative ? 'relative symlink' : 'symlink';
            } catch (\Throwable $e) {
                $io->warning(sprintf('Could not symlink (%s) — falling back to copy.', $e->getMessage()));
                $fs->mirror($source, $target);
                $method = 'copy';
            }
        } else {
            $fs->mirror($source, $target);
        }

        $io->success(sprintf('Installed ADP panel + toolbar assets at %s (%s from %s).', $target, $method, $source));
        $io->note(
            'The bundle auto-detects the published copy. Set '
            . '`app_dev_panel.panel.static_url: /bundles/appdevpanel` in '
            . 'config/packages/app_dev_panel.yaml only to override the default.',
        );

        return Command::SUCCESS;
    }
}
