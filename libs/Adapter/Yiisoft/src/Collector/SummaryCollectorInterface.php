<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector;

/**
 * Summary data collector responsibility is to collect summary data for a collector.
 * Summary is used to display a list of previous requests and select one to display full info.
 * Its data set is specific to the list and is reduced compared to full data collected
 * in {@see CollectorInterface}.
 */
interface SummaryCollectorInterface extends CollectorInterface
{
    /**
     * @return array Summary payload. Keys may cross with any other summary collectors.
     */
    public function getSummary(): array;
}
