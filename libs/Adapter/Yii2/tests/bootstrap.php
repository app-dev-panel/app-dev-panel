<?php

declare(strict_types=1);

/**
 * Test bootstrap for Yii 2 adapter tests.
 *
 * Sets up the Yii 2 framework environment for PHPUnit:
 * - Disables Yii 2's error handler (PHPUnit manages errors)
 * - Defines debug/env constants
 * - Loads autoloader and framework class
 */

if (!defined('YII_ENABLE_ERROR_HANDLER')) {
    define('YII_ENABLE_ERROR_HANDLER', false);
}
if (!defined('YII_DEBUG')) {
    define('YII_DEBUG', true);
}
if (!defined('YII_ENV')) {
    define('YII_ENV', 'test');
}

// Root autoloader (from monorepo root)
$rootAutoload = dirname(__DIR__, 4) . '/vendor/autoload.php';
if (file_exists($rootAutoload)) {
    require_once $rootAutoload;
}

// Load Yii 2 framework class
$yiiPath = dirname(__DIR__, 4) . '/vendor/yiisoft/yii2/Yii.php';
if (file_exists($yiiPath)) {
    require_once $yiiPath;
}
