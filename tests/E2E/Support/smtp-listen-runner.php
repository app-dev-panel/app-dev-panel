<?php

declare(strict_types=1);

/**
 * Standalone entry point used exclusively by tests/E2E/SmtpListenerE2ETest.php.
 * Boots a Symfony Console application with only the SmtpListenCommand so the test
 * can spawn the real CLI command in a subprocess.
 */

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use AppDevPanel\Cli\Command\SmtpListenCommand;
use Symfony\Component\Console\Application;

$app = new Application('adp-smtp-e2e', 'test');
$command = new SmtpListenCommand();
$app->add($command);
$app->setDefaultCommand($command->getName() ?? 'mail:listen', true);
$app->run();
