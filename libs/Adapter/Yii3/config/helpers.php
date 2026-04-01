<?php

declare(strict_types=1);

/**
 * Check whether the debug panel is enabled in the given params array.
 *
 * @param array $params The application parameters.
 */
function isAppDevPanelEnabled(array $params): bool
{
    return (bool) ($params['app-dev-panel/yii3']['enabled'] ?? false);
}
