<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2;

use yii\base\Application;
use yii\web\Application as WebApplication;
use yii\web\UrlManager;

/**
 * Surfaces install-time problems that silently break the panel or toolbar.
 *
 * Logged via the framework logger (category {@see Bootstrap::LOG_CATEGORY}) so
 * they show up in runtime/logs/app.log and in ADP's own log collector. The
 * user's config is never mutated — the problem is just made loud enough to
 * discover without a debugger. Called once per process by {@see Bootstrap}.
 */
final class InstallWarnings
{
    public static function emit(Application $app): void
    {
        if (!$app instanceof WebApplication) {
            return;
        }

        self::warnIfYiiDebugRegistered($app);
        self::warnIfPrettyUrlsDisabled($app);
    }

    private static function warnIfYiiDebugRegistered(WebApplication $app): void
    {
        // Long-running servers (RoadRunner/Swoole) reuse the PHP process across
        // requests. The warning only needs to fire once per process.
        static $warned = false;
        if ($warned || !$app->hasModule('debug')) {
            return;
        }

        $class = self::moduleClass($app->getModules()['debug'] ?? null);
        if ($class === null || !str_contains($class, 'yii\\debug\\Module')) {
            return;
        }

        $warned = true;
        \Yii::warning(
            'yiisoft/yii2-debug is registered as module "debug" alongside ADP. '
            . 'Both handle routes under /debug/* — yii2-debug will intercept '
            . 'the panel. Remove "debug" from bootstrap[] and modules[] in your '
            . 'application config, or use ADP\'s $routePrefix to mount the panel '
            . 'under a different path. See website/guide/adapters/yii2.md.',
            Bootstrap::LOG_CATEGORY,
        );
    }

    /**
     * Class name behind a `modules[]` entry: an instantiated module, a config array, or unknown.
     */
    private static function moduleClass(mixed $moduleConfig): ?string
    {
        if (is_object($moduleConfig)) {
            return $moduleConfig::class;
        }

        $class = is_array($moduleConfig) ? $moduleConfig['class'] ?? null : null;

        return is_string($class) ? $class : null;
    }

    private static function warnIfPrettyUrlsDisabled(WebApplication $app): void
    {
        static $warned = false;
        if ($warned) {
            return;
        }

        $urlManager = $app->get('urlManager', false);
        if (!$urlManager instanceof UrlManager || $urlManager->enablePrettyUrl) {
            return;
        }

        $warned = true;
        \Yii::warning(
            'ADP requires UrlManager::$enablePrettyUrl = true — without pretty '
            . 'URLs the /debug routes fall back to r=… parsing and the panel '
            . 'returns 404 / the homepage. Enable pretty URLs in config/web.php '
            . 'under components.urlManager.',
            Bootstrap::LOG_CATEGORY,
        );
    }
}
