<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2;

use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\web\Application as WebApplication;
use yii\web\UrlManager;

/**
 * Yii 2 bootstrap component that registers the ADP debug module.
 *
 * Registered automatically via composer.json "extra.bootstrap".
 * Configures the 'app-dev-panel' module if ADP is enabled and the app is in debug mode.
 */
final class Bootstrap implements BootstrapInterface
{
    public const LOG_CATEGORY = 'app-dev-panel';

    public function bootstrap($app): void
    {
        if (!$this->shouldEnable($app)) {
            return;
        }

        $this->warnOnKnownConflicts($app);

        // Register the module if not already configured
        if (!$app->hasModule('app-dev-panel')) {
            $app->setModule('app-dev-panel', [
                'class' => Module::class,
            ]);
        }

        // Ensure the module is bootstrapped
        $module = $app->getModule('app-dev-panel');
        if ($module instanceof Module) {
            $module->bootstrap($app);
        }
    }

    private function shouldEnable(Application $app): bool
    {
        // Respect explicit configuration
        if ($app->hasModule('app-dev-panel')) {
            $config = $app->getModules()['app-dev-panel'] ?? [];
            if (is_array($config) && array_key_exists('enabled', $config) && $config['enabled'] === false) {
                return false;
            }
            return true;
        }

        // Auto-enable only in debug mode
        return YII_DEBUG;
    }

    /**
     * Surface install-time problems that silently break the panel or toolbar.
     *
     * Logged via the framework logger so they show up in runtime/logs/app.log
     * and in ADP's own log collector. We do not mutate the user's config —
     * just make the problem loud enough to discover without a debugger.
     */
    private function warnOnKnownConflicts(Application $app): void
    {
        if ($app instanceof WebApplication) {
            $this->warnIfYiiDebugRegistered($app);
            $this->warnIfPrettyUrlsDisabled($app);
        }
    }

    private function warnIfYiiDebugRegistered(WebApplication $app): void
    {
        if (!$app->hasModule('debug')) {
            return;
        }

        $modules = $app->getModules();
        $debugConfig = $modules['debug'] ?? null;
        $class = is_array($debugConfig)
            ? $debugConfig['class'] ?? null
            : (is_object($debugConfig) ? $debugConfig::class : null);

        if (!is_string($class) || !str_contains($class, 'yii\\debug\\Module')) {
            return;
        }

        \Yii::warning(
            'yiisoft/yii2-debug is registered as module "debug" alongside ADP. '
            . 'Both handle routes under /debug/* — yii2-debug will intercept '
            . 'the panel. Remove "debug" from bootstrap[] and modules[] in your '
            . 'application config, or use ADP\'s $routePrefix to mount the panel '
            . 'under a different path. See website/guide/adapters/yii2.md.',
            self::LOG_CATEGORY,
        );
    }

    private function warnIfPrettyUrlsDisabled(WebApplication $app): void
    {
        $urlManager = $app->get('urlManager', false);
        if (!$urlManager instanceof UrlManager) {
            return;
        }

        if ($urlManager->enablePrettyUrl) {
            return;
        }

        \Yii::warning(
            'ADP requires UrlManager::$enablePrettyUrl = true — without pretty '
            . 'URLs the /debug routes fall back to r=… parsing and the panel '
            . 'returns 404 / the homepage. Enable pretty URLs in config/web.php '
            . 'under components.urlManager.',
            self::LOG_CATEGORY,
        );
    }
}
