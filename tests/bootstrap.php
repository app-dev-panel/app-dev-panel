<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// NullFilter: stream filter that consumes data (used to suppress STDERR in separate process tests)
if (!class_exists('NullFilter', false)) {
    final class NullFilter extends php_user_filter
    {
        /** @param resource $in */
        /** @param resource $out */
        public function filter($in, $out, &$consumed, $closing): int
        {
            while ($bucket = stream_bucket_make_writeable($in)) {
                $consumed += $bucket->datalen;
            }
            return PSFS_PASS_ON;
        }
    }
}

// Bootstrap Yii 2 framework class if available (needed for Yii2 adapter tests)
$yii2Path = __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
if (file_exists($yii2Path) && !class_exists('Yii', false)) {
    defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', false);
    defined('YII_DEBUG') or define('YII_DEBUG', true);
    defined('YII_ENV') or define('YII_ENV', 'test');
    require_once $yii2Path;
}
