<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

final class Plugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io): void {}

    public function deactivate(Composer $composer, IOInterface $io): void {}

    public function uninstall(Composer $composer, IOInterface $io): void {}

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'onPostInstall',
            ScriptEvents::POST_UPDATE_CMD => 'onPostUpdate',
        ];
    }

    public function onPostInstall(Event $event): void
    {
        $this->installFrontend($event->getIO());
    }

    public function onPostUpdate(Event $event): void
    {
        $this->installFrontend($event->getIO());
    }

    private function installFrontend(IOInterface $io): void
    {
        $installer = new FrontendInstaller($io);
        $installer->install();
    }
}
