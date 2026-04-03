<?php

declare(strict_types=1);

namespace AppDevPanel\Cli;

use AppDevPanel\Api\Debug\Repository\CollectorRepository;
use AppDevPanel\Cli\Command\DebugDumpCommand;
use AppDevPanel\Cli\Command\DebugQueryCommand;
use AppDevPanel\Cli\Command\DebugResetCommand;
use AppDevPanel\Cli\Command\DebugServerBroadcastCommand;
use AppDevPanel\Cli\Command\DebugServerCommand;
use AppDevPanel\Cli\Command\DebugSummaryCommand;
use AppDevPanel\Cli\Command\DebugTailCommand;
use AppDevPanel\Cli\Command\FrontendUpdateCommand;
use AppDevPanel\Cli\Command\McpServeCommand;
use AppDevPanel\Cli\Command\ServeCommand;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\FileStorage;
use Symfony\Component\Console\Application;

final class ApplicationFactory
{
    /**
     * Default path where the frontend assets are stored after download.
     */
    public static function getDefaultFrontendPath(): string
    {
        return self::getDataDir() . '/frontend';
    }

    /**
     * Default storage path for debug data.
     */
    public static function getDefaultStoragePath(): string
    {
        return sys_get_temp_dir() . '/adp';
    }

    /**
     * ADP data directory (for frontend assets, version files, etc.).
     */
    public static function getDataDir(): string
    {
        $xdgData = getenv('XDG_DATA_HOME');
        if (is_string($xdgData) && $xdgData !== '') {
            return $xdgData . '/adp';
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $appData = getenv('LOCALAPPDATA');
            if (is_string($appData) && $appData !== '') {
                return $appData . '/adp';
            }
        }

        $home = getenv('HOME');
        if (is_string($home) && $home !== '') {
            return $home . '/.local/share/adp';
        }

        return sys_get_temp_dir() . '/adp-data';
    }

    public static function create(): Application
    {
        $application = new Application('ADP — Application Development Panel', 'dev');

        $storagePath = self::getDefaultStoragePath();
        $idGenerator = new DebuggerIdGenerator();
        $storage = new FileStorage($storagePath, $idGenerator);
        $collectorRepository = new CollectorRepository($storage);
        $debugger = new Debugger($idGenerator, $storage, []);

        $application->addCommands([
            // Standalone commands (no storage needed)
            new DebugServerCommand(),
            new DebugServerBroadcastCommand(),
            new ServeCommand(),
            new McpServeCommand(),
            new FrontendUpdateCommand(),

            // Storage-dependent commands
            new DebugQueryCommand($collectorRepository),
            new DebugDumpCommand($collectorRepository),
            new DebugSummaryCommand($collectorRepository),
            new DebugTailCommand($collectorRepository),
            new DebugResetCommand($storage, $debugger),
        ]);

        return $application;
    }
}
