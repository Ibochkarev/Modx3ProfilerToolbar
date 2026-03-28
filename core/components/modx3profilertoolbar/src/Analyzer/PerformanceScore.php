<?php
/**
 * Calculates 0-100 performance score from trace metrics.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Analyzer;

class PerformanceScore
{
    /**
     * @param array $traceData metrics, events, sql
     * @return int 0-100
     */
    public static function calculate(array $traceData): int
    {
        $metrics = $traceData['metrics'] ?? [];
        $totalTime = (float) ($metrics['total_time'] ?? 0);
        $sqlTime = (float) ($metrics['sql_time'] ?? 0);
        $memoryPeak = (int) ($metrics['memory_peak'] ?? 0);

        $score = 100;
        if ($totalTime > 1.0) $score -= 30;
        elseif ($totalTime > 0.5) $score -= 15;
        elseif ($totalTime > 0.2) $score -= 5;

        if ($sqlTime > 0.3) $score -= 20;
        elseif ($sqlTime > 0.1) $score -= 10;

        if ($memoryPeak > 128 * 1024 * 1024) $score -= 15;
        elseif ($memoryPeak > 64 * 1024 * 1024) $score -= 5;

        $advisor = $traceData['advisor'] ?? [];
        $score -= min(20, count($advisor) * 5);

        return max(0, min(100, $score));
    }
}
