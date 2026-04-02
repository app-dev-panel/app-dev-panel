<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2;

use yii\base\Application;
use yii\base\BootstrapInterface;

/**
 * Yii 2 bootstrap component that registers the ADP debug module.
 *
 * Registered automatically via composer.json "extra.bootstrap".
 * Configures the 'adp' module if ADP is enabled and the app is in debug mode.
 */
final class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        if (!$this->shouldEnable($app)) {
            return;
        }

        // Register the module if not already configured
        if (!$app->hasModule('adp')) {
            $app->setModule('adp', [
                'class' => Module::class,
            ]);
        }

        // Ensure the module is bootstrapped
        $module = $app->getModule('adp');
        if ($module instanceof Module) {
            $module->bootstrap($app);
        }
    }

    private function shouldEnable(Application $app): bool
    {
        // Respect explicit configuration
        if ($app->hasModule('adp')) {
            $config = $app->getModules()['adp'] ?? [];
            if (is_array($config) && array_key_exists('enabled', $config) && $config['enabled'] === false) {
                return false;
            }
            return true;
        }

        // Auto-enable only in debug mode
        return YII_DEBUG;
    }
}
